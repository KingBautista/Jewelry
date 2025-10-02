import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import axiosClient from '../axios-client';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import LoadingSpinner from '../components/LoadingSpinner';

const InvoiceDetail = () => {
  const { id } = useParams();
  const [loading, setLoading] = useState(true);
  const [invoice, setInvoice] = useState(null);

  useEffect(() => {
    fetchInvoice();
  }, [id]);

  const fetchInvoice = async () => {
    try {
      const response = await axiosClient.get(`/customer/invoices/${id}`);
      setInvoice(response.data.data);
    } catch (error) {
      console.error('Error fetching invoice:', error);
    } finally {
      setLoading(false);
    }
  };

  const downloadPdf = async () => {
    try {
      const response = await axiosClient.get(`/customer/invoices/${id}/pdf`, {
        responseType: 'blob'
      });
      
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `invoice-${invoice.invoice_number}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Error downloading PDF:', error);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'PHP'
    }).format(amount);
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

  if (loading) {
    return <LoadingSpinner />;
  }

  if (!invoice) {
    return (
      <div className="container-fluid">
        <div className="text-center py-5">
          <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="text-muted mb-3" style={{ fontSize: '3rem' }} />
          <h5 className="text-muted">Invoice not found</h5>
          <Link to="/invoices" className="btn btn-primary">Back to Invoices</Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid">
      <div className="row">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h1 className="h3 mb-0" style={{ color: 'var(--text-color)' }}>
                <FontAwesomeIcon icon={solidIconMap.fileInvoice} className="me-2 text-champagne" />
                Invoice #{invoice.invoice_number}
              </h1>
              <p className="text-muted mb-0">Issue Date: {new Date(invoice.issue_date).toLocaleDateString()}</p>
            </div>
            <div className="d-flex gap-2">
              <button 
                className="btn btn-outline-primary"
                onClick={downloadPdf}
              >
                <FontAwesomeIcon icon={solidIconMap.download} className="me-2" />
                Download PDF
              </button>
              {invoice.payment_status !== 'fully_paid' && (
                <Link 
                  to={`/payment-submission?invoice=${invoice.id}`} 
                  className="btn btn-primary"
                >
                  <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-2" />
                  Submit Payment
                </Link>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="row g-4">
        {/* Invoice Details */}
        <div className="col-lg-8">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <h5 className="mb-0">Invoice Details</h5>
            </div>
            <div className="card-body">
              <div className="row mb-4">
                <div className="col-md-6">
                  <h6 className="fw-semibold">Invoice Information</h6>
                  <p className="mb-1"><strong>Invoice Number:</strong> {invoice.invoice_number}</p>
                  <p className="mb-1"><strong>Issue Date:</strong> {new Date(invoice.issue_date).toLocaleDateString()}</p>
                  <p className="mb-1"><strong>Due Date:</strong> {invoice.due_date ? new Date(invoice.due_date).toLocaleDateString() : 'N/A'}</p>
                  <p className="mb-1"><strong>Status:</strong> {getStatusBadge(invoice.payment_status)}</p>
                </div>
                <div className="col-md-6">
                  <h6 className="fw-semibold">Payment Information</h6>
                  <p className="mb-1"><strong>Total Amount:</strong> {formatCurrency(invoice.total_amount)}</p>
                  <p className="mb-1"><strong>Paid Amount:</strong> {formatCurrency(invoice.total_paid_amount)}</p>
                  <p className="mb-1"><strong>Remaining Balance:</strong> {formatCurrency(invoice.remaining_balance)}</p>
                </div>
              </div>

              {/* Invoice Items */}
              {invoice.items && invoice.items.length > 0 && (
                <div className="table-responsive">
                  <h6 className="fw-semibold mb-3">Invoice Items</h6>
                  <table className="table table-bordered">
                    <thead>
                      <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      {invoice.items.map((item, index) => (
                        <tr key={index}>
                          <td>{item.description || item.product_name}</td>
                          <td>1</td>
                          <td>{formatCurrency(item.price || 0)}</td>
                          <td>{formatCurrency(item.price || 0)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              {/* Notes */}
              {invoice.notes && (
                <div className="mt-4">
                  <h6 className="fw-semibold">Notes</h6>
                  <p className="text-muted">{invoice.notes}</p>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Payment Summary */}
        <div className="col-lg-4">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <h5 className="mb-0">Payment Summary</h5>
            </div>
            <div className="card-body">
              <div className="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>{formatCurrency(invoice.subtotal)}</span>
              </div>
              {invoice.tax_amount > 0 && (
                <div className="d-flex justify-content-between mb-2">
                  <span>Tax:</span>
                  <span>{formatCurrency(invoice.tax_amount)}</span>
                </div>
              )}
              {invoice.fee_amount > 0 && (
                <div className="d-flex justify-content-between mb-2">
                  <span>Fee:</span>
                  <span>{formatCurrency(invoice.fee_amount)}</span>
                </div>
              )}
              {invoice.discount_amount > 0 && (
                <div className="d-flex justify-content-between mb-2">
                  <span>Discount:</span>
                  <span className="text-success">-{formatCurrency(invoice.discount_amount)}</span>
                </div>
              )}
              <hr />
              <div className="d-flex justify-content-between fw-bold">
                <span>Total Amount:</span>
                <span>{formatCurrency(invoice.total_amount)}</span>
              </div>
              <div className="d-flex justify-content-between text-success">
                <span>Paid Amount:</span>
                <span>{formatCurrency(invoice.total_paid_amount)}</span>
              </div>
              <hr />
              <div className="d-flex justify-content-between fw-bold">
                <span>Remaining Balance:</span>
                <span className={invoice.remaining_balance > 0 ? 'text-danger' : 'text-success'}>
                  {formatCurrency(invoice.remaining_balance)}
                </span>
              </div>
            </div>
          </div>

          {/* Payment Actions */}
          {invoice.payment_status !== 'fully_paid' && (
            <div className="card shadow-sm mt-4">
              <div className="card-header bg-warning text-dark">
                <h5 className="mb-0">Payment Required</h5>
              </div>
              <div className="card-body">
                <p className="text-muted mb-3">
                  This invoice has an outstanding balance of {formatCurrency(invoice.remaining_balance)}.
                </p>
                <Link 
                  to={`/payment-submission?invoice=${invoice.id}`} 
                  className="btn btn-primary w-100"
                >
                  <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-2" />
                  Submit Payment
                </Link>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default InvoiceDetail;
