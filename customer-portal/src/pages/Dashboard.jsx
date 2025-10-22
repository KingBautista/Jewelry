import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axiosClient from '../axios-client';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import LoadingSpinner from '../components/LoadingSpinner';

// Helper function to format numbers with commas
const formatNumber = (number) => {
  if (number === null || number === undefined) return '0';
  return Number(number).toLocaleString();
};

const Dashboard = () => {
  const [loading, setLoading] = useState(true);
  const [dashboardData, setDashboardData] = useState({
    overview: {},
    recentInvoices: [],
    upcomingDues: [],
    overdueInvoices: []
  });

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [overviewRes, invoicesRes] = await Promise.all([
        axiosClient.get('/customer/dashboard/overview'),
        axiosClient.get('/customer/invoices?limit=5')
      ]);


      setDashboardData({
        overview: overviewRes.data,
        recentInvoices: invoicesRes.data.data || [],
        upcomingDues: overviewRes.data.upcoming_dues || [],
        overdueInvoices: overviewRes.data.overdue_invoices || []
      });
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <LoadingSpinner />;
  }

  const { overview, recentInvoices, upcomingDues, overdueInvoices } = dashboardData;

  return (
    <div className="container-fluid">
      <div className="row">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h3 mb-0" style={{ color: 'var(--text-color)' }}>
              <FontAwesomeIcon icon={solidIconMap.chartLine} className="me-2 text-champagne" />
              Dashboard
            </h1>
          </div>
        </div>
      </div>

      {/* Overview Cards */}
      <div className="row g-3 g-md-4 mb-4">
        <div className="col-6 col-lg-3">
          <div className="card h-100 shadow-sm">
            <div className="card-body text-center p-3 p-md-4">
              <FontAwesomeIcon 
                icon={solidIconMap.fileInvoice} 
                className="text-champagne mb-2 mb-md-3" 
                style={{ fontSize: 'clamp(1.5rem, 4vw, 2.5rem)' }} 
              />
              <h3 className="fw-bold mb-1" style={{ fontSize: 'clamp(1rem, 3vw, 1.5rem)' }}>
                {formatNumber(overview.total_invoices)}
              </h3>
              <p className="text-muted mb-0 small">Total Invoices</p>
            </div>
          </div>
        </div>
        
        <div className="col-6 col-lg-3">
          <div className="card h-100 shadow-sm">
            <div className="card-body text-center p-3 p-md-4">
              <FontAwesomeIcon 
                icon={solidIconMap.dollarSign} 
                className="text-success mb-2 mb-md-3" 
                style={{ fontSize: 'clamp(1.5rem, 4vw, 2.5rem)' }} 
              />
              <h3 className="fw-bold mb-1" style={{ fontSize: 'clamp(0.875rem, 2.5vw, 1.25rem)' }}>
                ₱{formatNumber(overview.total_paid)}
              </h3>
              <p className="text-muted mb-0 small">Total Paid</p>
            </div>
          </div>
        </div>
        
        <div className="col-6 col-lg-3">
          <div className="card h-100 shadow-sm">
            <div className="card-body text-center p-3 p-md-4">
              <FontAwesomeIcon 
                icon={solidIconMap.clock} 
                className="text-warning mb-2 mb-md-3" 
                style={{ fontSize: 'clamp(1.5rem, 4vw, 2.5rem)' }} 
              />
              <h3 className="fw-bold mb-1" style={{ fontSize: 'clamp(0.875rem, 2.5vw, 1.25rem)' }}>
                ₱{formatNumber(overview.outstanding_balance)}
              </h3>
              <p className="text-muted mb-0 small">Outstanding Balance</p>
            </div>
          </div>
        </div>
        
        <div className="col-6 col-lg-3">
          <div className="card h-100 shadow-sm">
            <div className="card-body text-center p-3 p-md-4">
              <FontAwesomeIcon 
                icon={solidIconMap.exclamationTriangle} 
                className="text-danger mb-2 mb-md-3" 
                style={{ fontSize: 'clamp(1.5rem, 4vw, 2.5rem)' }} 
              />
              <h3 className="fw-bold mb-1" style={{ fontSize: 'clamp(1rem, 3vw, 1.5rem)' }}>
                {formatNumber(overdueInvoices.length)}
              </h3>
              <p className="text-muted mb-0 small">Overdue Invoices</p>
            </div>
          </div>
        </div>
      </div>

      {/* Alerts */}
      {overdueInvoices.length > 0 && (
        <div className="row mb-4">
          <div className="col-12">
            <div className="alert alert-danger border-0 shadow-sm">
              <div className="d-flex align-items-center">
                <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-3" style={{ fontSize: '1.5rem' }} />
                <div>
                  <h5 className="alert-heading mb-1">Overdue Invoices Alert</h5>
                  <p className="mb-0">You have {overdueInvoices.length} overdue invoice(s) that require immediate attention.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {upcomingDues.length > 0 && (
        <div className="row mb-4">
          <div className="col-12">
            <div className="alert alert-warning border-0 shadow-sm">
              <div className="d-flex align-items-center">
                <FontAwesomeIcon icon={solidIconMap.clock} className="me-3" style={{ fontSize: '1.5rem' }} />
                <div>
                  <h5 className="alert-heading mb-1">Upcoming Payment Due</h5>
                  <p className="mb-0">You have {upcomingDues.length} invoice(s) with upcoming due dates.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      <div className="row g-3 g-lg-4">
        {/* Recent Invoices */}
        <div className="col-12 col-lg-8">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <div className="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <h5 className="mb-2 mb-md-0">
                  <FontAwesomeIcon icon={solidIconMap.fileInvoice} className="me-2" />
                  Recent Invoices
                </h5>
                <Link to="/invoices" className="btn btn-sm btn-outline-light">
                  View All
                </Link>
              </div>
            </div>
            <div className="card-body p-0">
              {recentInvoices.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-hover mb-0">
                    <thead className="d-none d-md-table-header-group">
                      <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {recentInvoices.map((invoice) => (
                        <tr key={invoice.id}>
                          {/* Mobile card layout */}
                          <td className="d-md-none">
                            <div className="card border-0 bg-light">
                              <div className="card-body p-3">
                                <div className="d-flex justify-content-between align-items-start mb-2">
                                  <div>
                                    <div className="fw-semibold">{invoice.invoice_number}</div>
                                    <small className="text-muted">
                                      {new Date(invoice.issue_date).toLocaleDateString()}
                                    </small>
                                  </div>
                                  <span className={`badge ${
                                    invoice.payment_status === 'fully_paid' ? 'badge-success' :
                                    invoice.payment_status === 'partially_paid' ? 'badge-warning' :
                                    invoice.payment_status === 'overdue' ? 'badge-danger' : 'badge-info'
                                  }`}>
                                    {invoice.payment_status.replace('_', ' ').toUpperCase()}
                                  </span>
                                </div>
                                <div className="d-flex justify-content-between align-items-center">
                                  <div className="fw-semibold">₱{formatNumber(invoice.total_amount)}</div>
                                  <Link 
                                    to={`/invoices/${invoice.id}`} 
                                    className="btn btn-sm btn-outline-primary"
                                  >
                                    View
                                  </Link>
                                </div>
                              </div>
                            </div>
                          </td>
                          
                          {/* Desktop table layout */}
                          <td className="d-none d-md-table-cell fw-semibold">{invoice.invoice_number}</td>
                          <td className="d-none d-md-table-cell">{new Date(invoice.issue_date).toLocaleDateString()}</td>
                          <td className="d-none d-md-table-cell fw-semibold">₱{formatNumber(invoice.total_amount)}</td>
                          <td className="d-none d-md-table-cell">
                            <span className={`badge ${
                              invoice.payment_status === 'fully_paid' ? 'badge-success' :
                              invoice.payment_status === 'partially_paid' ? 'badge-warning' :
                              invoice.payment_status === 'overdue' ? 'badge-danger' : 'badge-info'
                            }`}>
                              {invoice.payment_status.replace('_', ' ').toUpperCase()}
                            </span>
                          </td>
                          <td className="d-none d-md-table-cell">
                            <Link 
                              to={`/invoices/${invoice.id}`} 
                              className="btn btn-sm btn-outline-primary"
                            >
                              View
                            </Link>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-5">
                  <FontAwesomeIcon icon={solidIconMap.fileInvoice} className="text-muted mb-3" style={{ fontSize: '3rem' }} />
                  <p className="text-muted">No invoices found</p>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="col-12 col-lg-4">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <h5 className="mb-0">
                <FontAwesomeIcon icon={solidIconMap.bolt} className="me-2" />
                Quick Actions
              </h5>
            </div>
            <div className="card-body">
              <div className="d-grid gap-2 gap-md-3">
                <Link to="/payment-submission" className="btn btn-primary">
                  <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-2" />
                  Submit Payment
                </Link>
                <Link to="/invoices" className="btn btn-outline-primary">
                  <FontAwesomeIcon icon={solidIconMap.fileInvoice} className="me-2" />
                  View All Invoices
                </Link>
                <Link to="/payment-history" className="btn btn-outline-primary">
                  <FontAwesomeIcon icon={solidIconMap.history} className="me-2" />
                  Payment History
                </Link>
              </div>
            </div>
          </div>

          {/* Upcoming Dues */}
          {upcomingDues.length > 0 && (
            <div className="card shadow-sm mt-3 mt-md-4">
              <div className="card-header bg-warning text-dark">
                <h5 className="mb-0">
                  <FontAwesomeIcon icon={solidIconMap.clock} className="me-2" />
                  Upcoming Dues
                </h5>
              </div>
              <div className="card-body">
                {upcomingDues.map((invoice) => (
                  <div key={invoice.id} className="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <div className="fw-semibold small">{invoice.invoice_number}</div>
                      <small className="text-muted">
                        Due: {new Date(invoice.due_date).toLocaleDateString()}
                      </small>
                    </div>
                    <div className="text-end">
                      <div className="fw-semibold small">₱{formatNumber(invoice.remaining_balance)}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
