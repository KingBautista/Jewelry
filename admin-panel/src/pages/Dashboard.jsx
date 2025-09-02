import React, { useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import axiosClient from '../axios-client';

const getCurrentDate = () => {
	const now = new Date();
	return now.toLocaleDateString(undefined, {
		weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
	});
};

export default function Dashboard() {
	const [dashboardData, setDashboardData] = useState({
		statistics: {
			total_users: 0,
			active_users: 0,
			today_registrations: 0,
		}
	});
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		loadDashboardData();
	}, []);

	const loadDashboardData = async () => {
		try {
			setLoading(true);
			const statsResponse = await axiosClient.get('/dashboard/statistics');

			setDashboardData({
				statistics: statsResponse.data.data,
			});
		} catch (error) {
			console.error('Error loading dashboard data:', error);
		} finally {
			setLoading(false);
		}
	};

	if (loading) {
		return (
			<div className="d-flex justify-content-center align-items-center" style={{ height: '50vh' }}>
				<div className="spinner-border text-primary" role="status">
					<span className="visually-hidden">Loading...</span>
				</div>
			</div>
		);
	}

	return (
		<div className="dashboard-metrics container-fluid">
			<div className="row g-3 mb-3">
				<div className="col-md-6">
					<div className="card text-center h-100">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.calendar} className="mb-2 text-primary" size="2x" />
							<h5 className="card-title">Current Date</h5>
							<p className="card-text fs-5 fw-bold">{getCurrentDate()}</p>
						</div>
					</div>
				</div>
				<div className="col-md-6">
					<div className="card text-center h-100">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.users} className="mb-2 text-info" size="2x" />
							<h5 className="card-title">Total Users</h5>
							<p className="card-text fs-4 fw-bold">{dashboardData.statistics.total_users}</p>
						</div>
					</div>
				</div>
			</div>
			<div className="row g-3">
				<div className="col-md-6">
					<div className="card text-center h-100">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.userCheck} className="mb-2 text-success" size="2x" />
							<h5 className="card-title">Active Users</h5>
							<p className="card-text fs-4 fw-bold">{dashboardData.statistics.active_users}</p>
						</div>
					</div>
				</div>
				<div className="col-md-6">
					<div className="card text-center h-100">
						<div className="card-body">
							<FontAwesomeIcon icon={solidIconMap.userPlus} className="mb-2 text-warning" size="2x" />
							<h5 className="card-title">Today's Registrations</h5>
							<p className="card-text fs-4 fw-bold">{dashboardData.statistics.today_registrations}</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}