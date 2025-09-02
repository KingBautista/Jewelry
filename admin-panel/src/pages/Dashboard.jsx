import React, { useEffect, useState, useRef } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import Chart from 'chart.js/auto';

const getCurrentDate = () => {
	const now = new Date();
	return now.toLocaleDateString(undefined, {
		weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
	});
};

export default function Dashboard() {
	const chartRef = useRef(null);
	const chartInstance = useRef(null);

	// Static data for the dashboard
	const [dashboardData] = useState({
		revenue: {
			currentMonth: 1250000,
			previousMonth: 1180000,
			yearlyTotal: 14500000
		},
		outstandingBalances: 450000,
		invoiceStats: {
			totalIssued: 156,
			totalSent: 142,
			totalCancelled: 14
		},
		paymentBreakdown: {
			paid: 1250000,
			pending: 450000
		},
		customerSummary: [
			{
				username: 'John D.',
				items: 4,
				paid: 12000,
				pending: 3500
			},
			{
				username: 'Maria S.',
				items: 3,
				paid: 8500,
				pending: 2200
			},
			{
				username: 'Robert L.',
				items: 2,
				paid: 6800,
				pending: 1800
			},
			{
				username: 'Sarah M.',
				items: 5,
				paid: 15200,
				pending: 4200
			},
			{
				username: 'David K.',
				items: 1,
				paid: 3200,
				pending: 800
			}
		],
		itemizedSummary: [
			{
				itemName: 'LV Bag',
				paid: 5000,
				pending: 1000,
				terms: '2/10',
				total: 6000
			},
			{
				itemName: 'Diamond Ring',
				paid: 8500,
				pending: 2500,
				terms: '3/12',
				total: 11000
			},
			{
				itemName: 'Gold Necklace',
				paid: 4200,
				pending: 800,
				terms: '1/5',
				total: 5000
			},
			{
				itemName: 'Pearl Earrings',
				paid: 2800,
				pending: 700,
				terms: '1/4',
				total: 3500
			},
			{
				itemName: 'Silver Bracelet',
				paid: 3200,
				pending: 800,
				terms: '2/8',
				total: 4000
			}
		],
		topCustomers: [
			{
				username: 'Sarah M.',
				items: 5,
				totalAmount: 19400
			},
			{
				username: 'John D.',
				items: 4,
				totalAmount: 15500
			},
			{
				username: 'Maria S.',
				items: 3,
				totalAmount: 10700
			},
			{
				username: 'Robert L.',
				totalAmount: 8600,
				items: 2
			},
			{
				username: 'David K.',
				items: 1,
				totalAmount: 4000
			}
		]
	});

	const formatCurrency = (amount) => {
		return `₱${amount.toLocaleString()}`;
	};

	const calculatePercentage = (value, total) => {
		return ((value / total) * 100).toFixed(1);
	};

	// Initialize Chart.js pie chart
	useEffect(() => {
		if (chartRef.current && !chartInstance.current) {
			const ctx = chartRef.current.getContext('2d');
			
			chartInstance.current = new Chart(ctx, {
				type: 'pie',
				data: {
					labels: ['Paid', 'Pending'],
					datasets: [{
						data: [dashboardData.paymentBreakdown.paid, dashboardData.paymentBreakdown.pending],
						backgroundColor: [
							'#198754', // Bootstrap success color
							'#ffc107'  // Bootstrap warning color
						],
						borderColor: [
							'#146c43', // Darker success
							'#e0a800'  // Darker warning
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
								}
							}
						},
						tooltip: {
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
	}, [dashboardData.paymentBreakdown]);

	return (
		<div className="dashboard-metrics container-fluid">
			{/* Header */}
			<div className="row mb-4">
				<div className="col-12">
					<div className="card">
						<div className="card-body text-center">
							<FontAwesomeIcon icon={solidIconMap.calendar} className="mb-3 text-primary" size="3x" />
							<h3 className="card-title">Financial Dashboard</h3>
							<p className="card-text text-muted">Quick view of financial health and customer activity</p>
							<h5 className="text-info">{getCurrentDate()}</h5>
						</div>
					</div>
				</div>
			</div>

			{/* Revenue Overview */}
			<div className="row g-3 mb-4">
				<div className="col-md-4">
					<div className="card text-center h-100 border-success">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.chart} className="mb-2 text-success" size="2x" />
							<h6 className="card-title">Current Month Revenue</h6>
							<p className="card-text fs-4 fw-bold text-success">{formatCurrency(dashboardData.revenue.currentMonth)}</p>
						</div>
					</div>
				</div>
				<div className="col-md-4">
					<div className="card text-center h-100 border-info">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.chart} className="mb-2 text-info" size="2x" />
							<h6 className="card-title">Previous Month Revenue</h6>
							<p className="card-text fs-4 fw-bold text-info">{formatCurrency(dashboardData.revenue.previousMonth)}</p>
						</div>
					</div>
				</div>
				<div className="col-md-4">
					<div className="card text-center h-100 border-primary">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.chart} className="mb-2 text-primary" size="2x" />
							<h6 className="card-title">Yearly Total Revenue</h6>
							<p className="card-text fs-4 fw-bold text-primary">{formatCurrency(dashboardData.revenue.yearlyTotal)}</p>
						</div>
					</div>
				</div>
			</div>

			{/* Outstanding Balances & Invoice Stats */}
			<div className="row g-3 mb-4">
				<div className="col-md-6">
					<div className="card text-center border-warning">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="mb-2 text-warning" size="2x" />
							<h6 className="card-title">Outstanding Balances</h6>
							<p className="card-text fs-3 fw-bold text-warning">{formatCurrency(dashboardData.outstandingBalances)}</p>
							<small className="text-muted">Total unpaid amounts across all customers</small>
						</div>
					</div>
				</div>
				<div className="col-md-6">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0">
								<FontAwesomeIcon icon={solidIconMap.file} className="me-2" />
								Invoice Statistics
							</h6>
						</div>
						<div className="card-body">
							<div className="row text-center">
								<div className="col-4">
									<div className="border-end">
										<h5 className="text-primary">{dashboardData.invoiceStats.totalIssued}</h5>
										<small className="text-muted">Total Issued</small>
									</div>
								</div>
								<div className="col-4">
									<div className="border-end">
										<h5 className="text-primary">{dashboardData.invoiceStats.totalSent}</h5>
										<small className="text-muted">Total Sent</small>
									</div>
								</div>
								<div className="col-4">
									<h5 className="text-danger">{dashboardData.invoiceStats.totalCancelled}</h5>
									<small className="text-muted">Total Cancelled</small>
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
							<h6 className="mb-0">
								<FontAwesomeIcon icon={solidIconMap.chart} className="me-2" />
								Payment Breakdown
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="row text-center">
								<div className="col-6">
									<div className="p-2">
										<div className="d-flex align-items-center justify-content-center mb-1">
											<div className="bg-success rounded-circle me-2" style={{ width: '16px', height: '16px' }}></div>
											<h6 className="text-success mb-0">{formatCurrency(dashboardData.paymentBreakdown.paid)}</h6>
										</div>
										<small className="text-muted">Paid ({calculatePercentage(dashboardData.paymentBreakdown.paid, dashboardData.paymentBreakdown.paid + dashboardData.paymentBreakdown.pending)}%)</small>
									</div>
								</div>
								<div className="col-6">
									<div className="p-2">
										<div className="d-flex align-items-center justify-content-center mb-1">
											<div className="bg-warning rounded-circle me-2" style={{ width: '16px', height: '16px' }}></div>
											<h6 className="text-warning mb-0">{formatCurrency(dashboardData.paymentBreakdown.pending)}</h6>
										</div>
										<small className="text-muted">Pending ({calculatePercentage(dashboardData.paymentBreakdown.pending, dashboardData.paymentBreakdown.paid + dashboardData.paymentBreakdown.pending)}%)</small>
									</div>
								</div>
							</div>
							<div className="chart-container" style={{ position: 'relative', height: '150px' }}>
								<canvas ref={chartRef}></canvas>
							</div>
						</div>
					</div>
				</div>
				<div className="col-md-6">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0">
								<FontAwesomeIcon icon={solidIconMap.users} className="me-2" />
								Top Customers
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="dashboard-table-responsive">
								<table className="table table-sm table-borderless mb-0" style={{ marginBottom: '0', marginTop: '0' }}>
									<thead>
										<tr style={{ margin: '0' }}>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', borderBottom: '1px solid #dee2e6', margin: '0' }}>Customer</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', borderBottom: '1px solid #dee2e6', margin: '0' }}>Items</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', borderBottom: '1px solid #dee2e6', margin: '0' }}>Total Amount</th>
										</tr>
									</thead>
									<tbody style={{ margin: '0' }}>
										{dashboardData.topCustomers.map((customer, index) => (
											<tr key={index} style={{ margin: '0' }}>
												<td style={{ padding: '0.25rem', margin: '0' }}>
													<span className={`badge bg-${index === 0 ? 'warning' : index === 1 ? 'secondary' : index === 2 ? 'info' : 'light'} text-dark`}>
														#{index + 1}
													</span>
													{' '}{customer.username}
												</td>
												<td style={{ padding: '0.25rem', margin: '0' }}>{customer.items}</td>
												<td className="fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(customer.totalAmount)}</td>
											</tr>
										))}
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
							<h6 className="mb-0">
								<FontAwesomeIcon icon={solidIconMap.users} className="me-2" />
								Customer Summary
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="dashboard-table-responsive">
								<table className="table table-sm table-striped table-hover mb-0" style={{ marginBottom: '0', marginTop: '0' }}>
									<thead className="table-dark">
										<tr style={{ margin: '0' }}>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Username</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Items</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Paid</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Pending</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Total</th>
										</tr>
									</thead>
									<tbody style={{ margin: '0' }}>
										{dashboardData.customerSummary.map((customer, index) => (
											<tr key={index} style={{ margin: '0' }}>
												<td className="fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{customer.username}</td>
												<td style={{ padding: '0.25rem', margin: '0' }}>
													<span className="badge bg-info">{customer.items}</span>
												</td>
												<td className="text-success fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(customer.paid)}</td>
												<td className="text-warning fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(customer.pending)}</td>
												<td className="fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(customer.paid + customer.pending)}</td>
											</tr>
										))}
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			{/* Itemized Summary Table */}
			<div className="row g-3 mb-4">
				<div className="col-12">
					<div className="card">
						<div className="card-header">
							<h6 className="mb-0">
								<FontAwesomeIcon icon={solidIconMap.list} className="me-2" />
								Itemized Summary per Customer
							</h6>
						</div>
						<div className="card-body" style={{ padding: '0.25rem' }}>
							<div className="dashboard-table-responsive">
								<table className="table table-sm table-striped table-hover mb-0" style={{ marginBottom: '0', marginTop: '0' }}>
									<thead className="table-dark">
										<tr style={{ margin: '0' }}>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Item Name</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Paid</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Pending</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Terms</th>
											<th style={{ fontSize: '0.8rem', padding: '0.25rem', margin: '0' }}>Total</th>
										</tr>
									</thead>
									<tbody style={{ margin: '0' }}>
										{dashboardData.itemizedSummary.map((item, index) => (
											<tr key={index} style={{ margin: '0' }}>
												<td className="fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{item.itemName}</td>
												<td className="text-success fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(item.paid)}</td>
												<td className="text-warning fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(item.pending)}</td>
												<td style={{ padding: '0.25rem', margin: '0' }}>
													<span className="badge bg-secondary">{item.terms}</span>
												</td>
												<td className="fw-bold" style={{ padding: '0.25rem', margin: '0' }}>{formatCurrency(item.total)}</td>
											</tr>
										))}
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