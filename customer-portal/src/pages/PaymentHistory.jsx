import { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import axiosClient from '../axios-client';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import LoadingSpinner from '../components/LoadingSpinner';

const PaymentHistory = () => {
  const location = useLocation();
  const [loading, setLoading] = useState(true);
  const [submissions, setSubmissions] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [message, setMessage] = useState(location.state?.message || '');

  useEffect(() => {
    fetchSubmissions();
  }, [currentPage]);

  const fetchSubmissions = async () => {
    try {
      setLoading(true);
      const response = await axiosClient.get(`/customer/payment-submissions?page=${currentPage}`);
      setSubmissions(response.data.data || []);
      setTotalPages(response.data.last_page || 1);
    } catch (error) {
      console.error('Error fetching payment submissions:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      'pending': { class: 'badge-warning', text: 'Pending Review' },
      'approved': { class: 'badge-success', text: 'Approved' },
      'rejected': { class: 'badge-danger', text: 'Rejected' }
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

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
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
              <FontAwesomeIcon icon={solidIconMap.history} className="me-2 text-champagne" />
              Payment History
            </h1>
          </div>
        </div>
      </div>

      {/* Success Message */}
      {message && (
        <div className="row mb-4">
          <div className="col-12">
            <div className="alert alert-success border-0 shadow-sm">
              <div className="d-flex align-items-center">
                <FontAwesomeIcon icon={solidIconMap.checkCircle} className="me-3" style={{ fontSize: '1.5rem' }} />
                <div>
                  <h5 className="alert-heading mb-1">Success!</h5>
                  <p className="mb-0">{message}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Payment Submissions */}
      <div className="row">
        <div className="col-12">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <h5 className="mb-0">Payment Submissions</h5>
            </div>
            <div className="card-body p-0">
              {submissions.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>Invoice #</th>
                        <th>Amount Paid</th>
                        <th>Reference #</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Reviewed</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {submissions.map((submission) => (
                        <tr key={submission.id}>
                          <td className="fw-semibold">{submission.invoice_number}</td>
                          <td className="fw-semibold">{formatCurrency(submission.amount_paid)}</td>
                          <td className="font-monospace">{submission.reference_number}</td>
                          <td>{getStatusBadge(submission.status)}</td>
                          <td>{formatDate(submission.submitted_at)}</td>
                          <td>
                            {submission.reviewed_at ? formatDate(submission.reviewed_at) : '-'}
                          </td>
                          <td>
                            <button 
                              className="btn btn-sm btn-outline-primary"
                              data-bs-toggle="modal"
                              data-bs-target={`#submissionModal${submission.id}`}
                            >
                              <FontAwesomeIcon icon={solidIconMap.eye} className="me-1" />
                              View Details
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-5">
                  <FontAwesomeIcon icon={solidIconMap.creditCard} className="text-muted mb-3" style={{ fontSize: '3rem' }} />
                  <h5 className="text-muted">No payment submissions found</h5>
                  <p className="text-muted">You haven't submitted any payments yet.</p>
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
            <nav aria-label="Payment submissions pagination">
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

      {/* Submission Detail Modals */}
      {submissions.map((submission) => (
        <div key={submission.id} className="modal fade" id={`submissionModal${submission.id}`} tabIndex="-1">
          <div className="modal-dialog modal-lg">
            <div className="modal-content">
              <div className="modal-header bg-champagne">
                <h5 className="modal-title">
                  Payment Submission Details - {submission.invoice_number}
                </h5>
                <button type="button" className="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div className="modal-body">
                <div className="row">
                  <div className="col-md-6">
                    <h6 className="fw-semibold">Payment Information</h6>
                    <p className="mb-1"><strong>Amount Paid:</strong> {formatCurrency(submission.amount_paid)}</p>
                    <p className="mb-1"><strong>Expected Amount:</strong> {formatCurrency(submission.expected_amount)}</p>
                    <p className="mb-1"><strong>Reference Number:</strong> {submission.reference_number}</p>
                    <p className="mb-1"><strong>Status:</strong> {getStatusBadge(submission.status)}</p>
                  </div>
                  <div className="col-md-6">
                    <h6 className="fw-semibold">Timeline</h6>
                    <p className="mb-1"><strong>Submitted:</strong> {formatDate(submission.submitted_at)}</p>
                    <p className="mb-1"><strong>Reviewed:</strong> {submission.reviewed_at ? formatDate(submission.reviewed_at) : 'Not yet reviewed'}</p>
                  </div>
                </div>

                {/* Receipt Images */}
                {submission.receipt_images && submission.receipt_images.length > 0 && (
                  <div className="mt-4">
                    <h6 className="fw-semibold">Receipt Images ({submission.receipt_images.length})</h6>
                    <div className="row g-2">
                      {submission.receipt_images.map((image, index) => {
                        // Handle different image path formats
                        let imageUrl = image;
                        
                        // Get the API base URL from environment variable
                        const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'https://api.illussso.com';
                        
                        // Construct proper image URLs based on the image path format
                        let finalImageUrl;
                        if (image.startsWith('payment-receipts/')) {
                          // Image is already in payment-receipts folder
                          finalImageUrl = `${apiBaseUrl}/storage/${image}`;
                        } else if (image.startsWith('receipts/')) {
                          // Image is in receipts folder
                          finalImageUrl = `${apiBaseUrl}/storage/${image}`;
                        } else if (image.startsWith('/storage/')) {
                          // Image already has storage path
                          finalImageUrl = `${apiBaseUrl}${image}`;
                        } else if (image.startsWith('http')) {
                          // Image is already a full URL
                          finalImageUrl = image;
                        } else {
                          // Default: assume it's a filename in payment-receipts folder
                          finalImageUrl = `${apiBaseUrl}/storage/payment-receipts/${image}`;
                        }
                        
                        const baseUrls = [
                          finalImageUrl,
                          `${apiBaseUrl}/storage/payment-receipts/${image}`,
                          `${apiBaseUrl}/storage/receipts/${image}`,
                          `${apiBaseUrl}/storage/${image}`,
                          image.startsWith('http') ? image : `${apiBaseUrl}/storage/payment-receipts/${image}`
                        ];
                        
                        
                        return (
                          <div key={index} className="col-md-3 col-sm-6 mb-3">
                            <div className="position-relative">
                              <img 
                                src={finalImageUrl} 
                                alt={`Receipt ${index + 1}`}
                                className="img-fluid rounded border shadow-sm"
                                style={{ 
                                  maxHeight: '120px', 
                                  maxWidth: '180px',
                                  objectFit: 'cover', 
                                  width: '100%',
                                  height: '120px',
                                  cursor: 'pointer',
                                  transition: 'transform 0.2s ease-in-out'
                                }}
                                onMouseEnter={(e) => {
                                  e.target.style.transform = 'scale(1.05)';
                                }}
                                onMouseLeave={(e) => {
                                  e.target.style.transform = 'scale(1)';
                                }}
                                onClick={() => {
                                  window.open(finalImageUrl, '_blank');
                                }}
                                onLoad={() => {
                                  // Image loaded successfully
                                }}
                              />
                              <small className="text-muted d-block mt-1 text-truncate" style={{ fontSize: '0.75rem' }}>
                                {image.split('/').pop()}
                              </small>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                )}

                {/* Rejection Reason */}
                {submission.status === 'rejected' && submission.rejection_reason && (
                  <div className="mt-4">
                    <div className="alert alert-danger">
                      <h6 className="fw-semibold">Rejection Reason:</h6>
                      <p className="mb-0">{submission.rejection_reason}</p>
                    </div>
                  </div>
                )}
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

export default PaymentHistory;
