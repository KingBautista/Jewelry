import React, { useEffect, useState, useRef } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import Chart from 'chart.js/auto';
import axiosClient from '../axios-client';

const getCurrentDate = () => {
	const now = new Date();
	return now.toLocaleDateString(undefined, {
		weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
	});
};

export default function Dashboard() {
	const chartRef = useRef(null);
	const chartInstance = useRef(null);
	const [dashboardData, setDashboardData] = useState({
		revenue: {
			currentMonth: 0,
			previousMonth: 0,
			yearlyTotal: 0,
			growthPercentage: 0
		},
		outstandingBalances: 0,
		invoiceStats: {
			totalIssued: 0,
			totalSent: 0,
			totalCancelled: 0
		},
		paymentBreakdown: {
			fully_paid: { count: 0, total_amount: 0, total_paid: 0, remaining_balance: 0 },
			partially_paid: { count: 0, total_amount: 0, total_paid: 0, remaining_balance: 0 },
			unpaid: { count: 0, total_amount: 0, total_paid: 0, remaining_balance: 0 },
			overdue: { count: 0, total_amount: 0, total_paid: 0, remaining_balance: 0 }
		},
		customerSummary: [],
		topCustomers: [],
		itemStatusSummary: {},
		loading: true,
		error: null
	});

	const formatCurrency = (amount) => {
		return `₱${amount.toLocaleString()}`;
	};

	const calculatePercentage = (value, total) => {
		return ((value / total) * 100).toFixed(1);
	};

	// Fetch dashboard data
	const fetchDashboardData = async () => {
		try {
			setDashboardData(prev => ({ ...prev, loading: true, error: null }));
			
			const [overviewRes, customersRes, paymentBreakdownRes, itemStatusRes] = await Promise.all([
				axiosClient.get('/dashboard/overview'),
				axiosClient.get('/dashboard/customers'),
				axiosClient.get('/dashboard/payment-breakdown'),
				axiosClient.get('/dashboard/item-status')
			]);

			const overview = overviewRes.data;
			const customers = customersRes.data;
			const paymentBreakdown = paymentBreakdownRes.data;
			const itemStatus = itemStatusRes.data;

			// Calculate total paid and pending for chart
			const totalPaid = Object.values(paymentBreakdown.payment_breakdown || {})
				.reduce((sum, status) => sum + (status.total_paid || 0), 0);
			const totalPending = Object.values(paymentBreakdown.payment_breakdown || {})
				.reduce((sum, status) => sum + (status.remaining_balance || 0), 0);

			setDashboardData({
				revenue: {
					currentMonth: overview.revenue?.current_month || 0,
					previousMonth: overview.revenue?.previous_month || 0,
					yearlyTotal: overview.revenue?.yearly_total || 0,
					growthPercentage: overview.revenue?.growth_percentage || 0
				},
				outstandingBalances: overview.outstanding_balances || 0,
				invoiceStats: {
					totalIssued: overview.invoice_stats?.total_issued || 0,
					totalSent: overview.invoice_stats?.total_sent || 0,
					totalCancelled: overview.invoice_stats?.total_cancelled || 0
				},
				paymentBreakdown: {
					paid: totalPaid,
					pending: totalPending,
					details: paymentBreakdown.payment_breakdown || {}
				},
				customerSummary: customers.top_customers?.map(customer => ({
					username: customer.name || customer.email,
					items: customer.invoice_count,
					paid: customer.total_paid,
					pending: customer.remaining_balance,
					total: customer.total_amount
				})) || [],
				topCustomers: customers.top_customers?.map(customer => ({
					username: customer.name || customer.email,
					items: customer.invoice_count,
					totalAmount: customer.total_amount
				})) || [],
				itemStatusSummary: itemStatus.status_summary || {},
				loading: false,
				error: null
			});
		} catch (error) {
			console.error('Error fetching dashboard data:', error);
			setDashboardData(prev => ({
				...prev,
				loading: false,
				error: 'Failed to load dashboard data'
			}));
		}
	};

	// Fetch data on component mount
	useEffect(() => {
		fetchDashboardData();
	}, []);

	// Initialize Chart.js pie chart
	useEffect(() => {
		if (chartRef.current && !chartInstance.current && !dashboardData.loading) {
			const ctx = chartRef.current.getContext('2d');
			
			chartInstance.current = new Chart(ctx, {
				type: 'pie',
				data: {
					labels: ['Paid', 'Pending'],
					datasets: [{
						data: [dashboardData.paymentBreakdown.paid, dashboardData.paymentBreakdown.pending],
						backgroundColor: [
							'#10b981', // Emerald green for paid
							'#f59e0b'  // Amber for pending
						],
						borderColor: [
							'#059669', // Darker emerald
							'#d97706'  // Darker amber
						],
						borderWidth: 2,
						hoverOffset: 4
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 10,
								usePointStyle: true,
								font: {
									size: 10
								},
								color: '#2C2C2C'
							}
						},
						tooltip: {
							backgroundColor: '#F7E7CE',
							titleColor: '#2C2C2C',
							bodyColor: '#2C2C2C',
							borderColor: '#E6D3B7',
							borderWidth: 1,
							callbacks: {
								label: function(context) {
									const label = context.label || '';
									const value = context.parsed;
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const percentage = ((value / total) * 100).toFixed(1);
									return `${label}: ${formatCurrency(value)} (${percentage}%)`;
								}
							}
						}
					}
				}
			});
		}

		// Cleanup function
		return () => {
			if (chartInstance.current) {
				chartInstance.current.destroy();
				chartInstance.current = null;
			}
		};
	}, [dashboardData.paymentBreakdown, dashboardData.loading]);

	// Show loading state
	if (dashboardData.loading) {
		return (
			<div className="dashboard-metrics container-fluid">
				<div className="row mb-4">
					<div className="col-12">
						<div className="card">
							<div className="card-body text-center">
								<div className="spinner-border text-primary" role="status">
									<span className="visually-hidden">Loading...</span>
								</div>
								<h5 className="mt-3">Loading Dashboard Data...</h5>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	}

	// Show error state
	if (dashboardData.error) {
		return (
			<div className="dashboard-metrics container-fluid">
				<div className="row mb-4">
					<div className="col-12">
						<div className="card border-danger">
							<div className="card-body text-center">
								<FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="mb-3 text-danger" size="3x" />
								<h5 className="text-danger">Error Loading Dashboard</h5>
								<p className="text-dark">{dashboardData.error}</p>
								<button className="btn btn-primary" onClick={fetchDashboardData}>
									<FontAwesomeIcon icon={solidIconMap.refresh} className="me-2" />
									Retry
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="dashboard-metrics container-fluid">
			{/* Header */}
			<div className="row mb-4">
				<div className="col-12">
					<div className="card">
						<div className="card-body text-center">
							<FontAwesomeIcon icon={solidIconMap.calendar} className="mb-3" style={{ color: '#2C2C2C' }} size="3x" />
							<h3 className="card-title" style={{ color: '#2C2C2C' }}>Financial Dashboard</h3>
							<p className="card-text" style={{ color: '#2C2C2C' }}>Quick view of financial health and customer activity</p>
							<h5 style={{ color: '#2C2C2C' }}>{getCurrentDate()}</h5>
							<button className="btn btn-outline-primary btn-sm mt-2" onClick={fetchDashboardData}>
								<FontAwesomeIcon icon={solidIconMap.refresh} className="me-1" />
								Refresh Data
							</button>
						</div>
					</div>
				</div>
			</div>

			{/* Revenue Overview */}
			<div className="row g-3 mb-4">
				<div className="col-md-4">
					<div className="card text-center h-100 border-success">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.dollarSign} className="mb-2" style={{ color: '#10b981' }} size="2x" />
							<h6 className="card-title" style={{ color: '#2C2C2C' }}>Current Month Revenue</h6>
							<p className="card-text fs-4 fw-bold" style={{ color: '#10b981' }}>{formatCurrency(dashboardData.revenue.currentMonth)}</p>
							{dashboardData.revenue.growthPercentage !== 0 && (
								<small className={`badge ${dashboardData.revenue.growthPercentage > 0 ? 'text-bg-success' : 'text-bg-danger'}`}>
									{dashboardData.revenue.growthPercentage > 0 ? '+' : ''}{dashboardData.revenue.growthPercentage}% vs last month
								</small>
							)}
						</div>
					</div>
				</div>
				<div className="col-md-4">
					<div className="card text-center h-100 border-info">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.chartLine} className="mb-2" style={{ color: '#3b82f6' }} size="2x" />
							<h6 className="card-title" style={{ color: '#2C2C2C' }}>Previous Month Revenue</h6>
							<p className="card-text fs-4 fw-bold" style={{ color: '#3b82f6' }}>{formatCurrency(dashboardData.revenue.previousMonth)}</p>
						</div>
					</div>
				</div>
				<div className="col-md-4">
					<div className="card text-center h-100 border-primary">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.chartPie} className="mb-2" style={{ color: '#8b5cf6' }} size="2x" />
							<h6 className="card-title" style={{ color: '#2C2C2C' }}>Yearly Total Revenue</h6>
							<p className="card-text fs-4 fw-bold" style={{ color: '#8b5cf6' }}>{formatCurrency(dashboardData.revenue.yearlyTotal)}</p>
						</div>
					</div>
				</div>
			</div>

			{/* Outstanding Balances & Invoice Stats */}
			<div className="row g-3 mb-4">
				<div className="col-md-6">
					<div className="card text-center border-warning">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.exclamationCircle} className="mb-2" style={{ color: '#f59e0b' }} size="2x" />
							<h6 className="card-title" style={{ color: '#2C2C2C' }}>Outstanding Balances</h6>
							<p className="card-text fs-3 fw-bold" style={{ color: '#f59e0b' }}>{formatCurrency(dashboardData.outstandingBalances)}</p>
							<small style={{ color: '#2C2C2C' }}>Total unpaid amounts across all customers</small>
						</div>
					</div>
				</div>
				<div className="col-md-6">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0" style={{ color: '#2C2C2C' }}>
								<FontAwesomeIcon icon={solidIconMap.file} className="me-2" />
								Invoice Statistics
							</h6>
						</div>
						<div className="card-body">
							<div className="row text-center">
								<div className="col-4">
									<div className="border-end">
										<h5 style={{ color: '#3b82f6' }}>{dashboardData.invoiceStats.totalIssued}</h5>
										<small style={{ color: '#2C2C2C' }}>Total Issued</small>
									</div>
								</div>
								<div className="col-4">
									<div className="border-end">
										<h5 style={{ color: '#3b82f6' }}>{dashboardData.invoiceStats.totalSent}</h5>
										<small style={{ color: '#2C2C2C' }}>Total Sent</small>
									</div>
								</div>
								<div className="col-4">
									<h5 style={{ color: '#ef4444' }}>{dashboardData.invoiceStats.totalCancelled}</h5>
									<small style={{ color: '#2C2C2C' }}>Total Cancelled</small>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			{/* Payment Breakdown Chart */}
			<div className="row g-3 mb-4">
				<div className="col-md-6">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0" style={{ color: '#2C2C2C' }}>
								<FontAwesomeIcon icon={solidIconMap.chartPie} className="me-2" />
								Payment Breakdown
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.5rem' }}>
							<div className="row text-center">
								<div className="col-6">
									<div className="p-2">
										<div className="d-flex align-items-center justify-content-center mb-1">
											<div className="rounded-circle me-2" style={{ width: '16px', height: '16px', backgroundColor: '#10b981' }}></div>
											<h6 className="mb-0" style={{ color: '#10b981' }}>{formatCurrency(dashboardData.paymentBreakdown.paid)}</h6>
										</div>
										<small style={{ color: '#2C2C2C' }}>Paid ({calculatePercentage(dashboardData.paymentBreakdown.paid, dashboardData.paymentBreakdown.paid + dashboardData.paymentBreakdown.pending)}%)</small>
									</div>
								</div>
								<div className="col-6">
									<div className="p-2">
										<div className="d-flex align-items-center justify-content-center mb-1">
											<div className="rounded-circle me-2" style={{ width: '16px', height: '16px', backgroundColor: '#f59e0b' }}></div>
											<h6 className="mb-0" style={{ color: '#f59e0b' }}>{formatCurrency(dashboardData.paymentBreakdown.pending)}</h6>
										</div>
										<small style={{ color: '#2C2C2C' }}>Pending ({calculatePercentage(dashboardData.paymentBreakdown.pending, dashboardData.paymentBreakdown.paid + dashboardData.paymentBreakdown.pending)}%)</small>
									</div>
								</div>
							</div>
							<div className="chart-container" style={{ position: 'relative', height: '200px' }}>
								<canvas ref={chartRef}></canvas>
							</div>
						</div>
					</div>
				</div>
				<div className="col-md-6">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0" style={{ color: '#2C2C2C' }}>
								<FontAwesomeIcon icon={solidIconMap.users} className="me-2" />
								Top Customers
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="dashboard-table-responsive">
								<table className="table table-sm table-modern mb-0" style={{ marginBottom: '0', marginTop: '0' }}>
									<thead>
										<tr style={{ margin: '0' }}>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Customer</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Items</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Total Amount</th>
										</tr>
									</thead>
									<tbody style={{ margin: '0' }}>
										{dashboardData.topCustomers.length > 0 ? (
											dashboardData.topCustomers.map((customer, index) => (
												<tr key={index} style={{ margin: '0' }}>
													<td style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>
														<span className={`badge text-bg-${index === 0 ? 'warning' : index === 1 ? 'secondary' : index === 2 ? 'info' : 'light'}`}>
															#{index + 1}
														</span>
														{' '}{customer.username}
													</td>
													<td style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>{customer.items}</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>{formatCurrency(customer.totalAmount)}</td>
												</tr>
											))
										) : (
											<tr>
												<td colSpan="3" className="text-center py-3" style={{ color: '#F7E7CE' }}>No customer data available</td>
											</tr>
										)}
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			{/* Customer Summary Table */}
			<div className="row g-3 mb-4">
				<div className="col-12">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0" style={{ color: '#2C2C2C' }}>
								<FontAwesomeIcon icon={solidIconMap.users} className="me-2" />
								Customer Summary
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="dashboard-table-responsive">
								<table className="table table-sm table-modern table-striped table-hover mb-0" style={{ marginBottom: '0', marginTop: '0' }}>
									<thead>
										<tr style={{ margin: '0' }}>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Username</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Items</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Paid</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Pending</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Total</th>
										</tr>
									</thead>
									<tbody style={{ margin: '0' }}>
										{dashboardData.customerSummary.length > 0 ? (
											dashboardData.customerSummary.map((customer, index) => (
												<tr key={index} style={{ margin: '0' }}>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>{customer.username}</td>
													<td style={{ padding: '0.25rem', margin: '0' }}>
														<span className="badge bg-info">{customer.items}</span>
													</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#10b981' }}>{formatCurrency(customer.paid)}</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#f59e0b' }}>{formatCurrency(customer.pending)}</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>{formatCurrency(customer.total)}</td>
												</tr>
											))
										) : (
											<tr>
												<td colSpan="5" className="text-center py-3" style={{ color: '#F7E7CE' }}>No customer data available</td>
											</tr>
										)}
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			{/* Payment Status Breakdown */}
			<div className="row g-3 mb-4">
				<div className="col-12">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0" style={{ color: '#2C2C2C' }}>
								<FontAwesomeIcon icon={solidIconMap.list} className="me-2" />
								Payment Status Breakdown
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="dashboard-table-responsive">
								<table className="table table-sm table-modern table-striped table-hover mb-0" style={{ marginBottom: '0', marginTop: '0' }}>
									<thead>
										<tr style={{ margin: '0' }}>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Status</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Count</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Total Amount</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Paid</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>Remaining</th>
										</tr>
									</thead>
									<tbody style={{ margin: '0' }}>
										{Object.keys(dashboardData.paymentBreakdown.details || {}).length > 0 ? (
											Object.entries(dashboardData.paymentBreakdown.details).map(([status, data]) => (
												<tr key={status} style={{ margin: '0' }}>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0' }}>
														<span className={`badge text-bg-${status === 'fully_paid' ? 'success' : status === 'partially_paid' ? 'warning' : status === 'unpaid' ? 'secondary' : 'danger'}`}>
															{status.replace('_', ' ').toUpperCase()}
														</span>
													</td>
													<td style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>{data.count}</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#F7E7CE' }}>{formatCurrency(data.total_amount)}</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#10b981' }}>{formatCurrency(data.total_paid)}</td>
													<td className="fw-bold" style={{ padding: '0.25rem', margin: '0', color: '#f59e0b' }}>{formatCurrency(data.remaining_balance)}</td>
												</tr>
											))
										) : (
											<tr>
												<td colSpan="5" className="text-center py-3" style={{ color: '#F7E7CE' }}>No payment data available</td>
											</tr>
										)}
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}