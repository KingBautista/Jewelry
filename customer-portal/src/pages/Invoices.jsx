import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axiosClient from '../axios-client';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import LoadingSpinner from '../components/LoadingSpinner';

const Invoices = () => {
  const [loading, setLoading] = useState(true);
  const [invoices, setInvoices] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    fetchInvoices();
  }, [currentPage, statusFilter, searchTerm]);

  const fetchInvoices = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        page: currentPage,
        search: searchTerm,
        status: statusFilter !== 'all' ? statusFilter : '',
      });

      const response = await axiosClient.get(`/customer/invoices?${params}`);
      setInvoices(response.data.data || []);
      setTotalPages(response.data.last_page || 1);
    } catch (error) {
      console.error('Error fetching invoices:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
    setCurrentPage(1);
  };

  const handleStatusFilter = (e) => {
    setStatusFilter(e.target.value);
    setCurrentPage(1);
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      'unpaid': { class: 'badge-danger', text: 'Unpaid' },
      'partially_paid': { class: 'badge-warning', text: 'Partially Paid' },
      'fully_paid': { class: 'badge-success', text: 'Fully Paid' },
      'overdue': { class: 'badge-danger', text: 'Overdue' }
    };
    
    const config = statusConfig[status] || { class: 'badge-info', text: status };
    return <span className={`badge ${config.class}`}>{config.text}</span>;
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'PHP'
    }).format(amount);
  };

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <div className="container-fluid">
      <div className="row">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h3 mb-0" style={{ color: 'var(--text-color)' }}>
              <FontAwesomeIcon icon={solidIconMap.fileInvoice} className="me-2 text-champagne" />
              My Invoices
            </h1>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="row mb-4">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-body">
              <div className="row g-3">
                <div className="col-md-6">
                  <div className="input-group">
                    <span className="input-group-text">
                      <FontAwesomeIcon icon={solidIconMap.search} />
                    </span>
                    <input
                      type="text"
                      className="form-control"
                      placeholder="Search invoices..."
                      value={searchTerm}
                      onChange={handleSearch}
                    />
                  </div>
                </div>
                <div className="col-md-3">
                  <select
                    className="form-select"
                    value={statusFilter}
                    onChange={handleStatusFilter}
                  >
                    <option value="all">All Status</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="fully_paid">Fully Paid</option>
                    <option value="overdue">Overdue</option>
                  </select>
                </div>
                <div className="col-md-3">
                  <button 
                    className="btn btn-outline-secondary w-100"
                    onClick={() => {
                      setSearchTerm('');
                      setStatusFilter('all');
                      setCurrentPage(1);
                    }}
                  >
                    <FontAwesomeIcon icon={solidIconMap.times} className="me-2" />
                    Clear Filters
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Invoices Table */}
      <div className="row">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-body p-0">
              {invoices.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>Invoice #</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {invoices.map((invoice) => (
                        <tr key={invoice.id}>
                          <td className="fw-semibold">{invoice.invoice_number}</td>
                          <td>{new Date(invoice.issue_date).toLocaleDateString()}</td>
                          <td>
                            {invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : 'N/A'}
                          </td>
                          <td className="fw-semibold">{formatCurrency(invoice.total_amount)}</td>
                          <td className="text-success">{formatCurrency(invoice.total_paid_amount)}</td>
                          <td className="fw-semibold">{formatCurrency(invoice.remaining_balance)}</td>
                          <td>{getStatusBadge(invoice.payment_status)}</td>
                          <td>
                            <div className="btn-group" role="group">
                              <Link 
                                to={`/invoices/${invoice.id}`} 
                                className="btn btn-sm btn-outline-primary"
                              >
                                <FontAwesomeIcon icon={solidIconMap.eye} className="me-1" />
                                View
                              </Link>
                              {invoice.payment_status !== 'fully_paid' && (
                                <Link 
                                  to={`/payment-submission?invoice=${invoice.id}`} 
                                  className="btn btn-sm btn-outline-success"
                                >
                                  <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-1" />
                                  Pay
                                </Link>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-5">
                  <FontAwesomeIcon icon={solidIconMap.fileInvoice} className="text-muted mb-3" style={{ fontSize: '3rem' }} />
                  <h5 className="text-muted">No invoices found</h5>
                  <p className="text-muted">No invoices match your current filters.</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="row mt-4">
          <div className="col-12">
            <nav aria-label="Invoices pagination">
              <ul className="pagination justify-content-center">
                <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                  <button 
                    className="page-link"
                    onClick={() => setCurrentPage(currentPage - 1)}
                    disabled={currentPage === 1}
                  >
                    Previous
                  </button>
                </li>
                
                {Array.from({ length: totalPages }, (_, i) => i + 1).map(page => (
                  <li key={page} className={`page-item ${currentPage === page ? 'active' : ''}`}>
                    <button 
                      className="page-link"
                      onClick={() => setCurrentPage(page)}
                    >
                      {page}
                    </button>
                  </li>
                ))}
                
                <li className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}>
                  <button 
                    className="page-link"
                    onClick={() => setCurrentPage(currentPage + 1)}
                    disabled={currentPage === totalPages}
                  >
                    Next
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      )}
    </div>
  );
};

export default Invoices;
