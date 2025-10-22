import { useState, useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import axiosClient from '../axios-client';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import LoadingSpinner from '../components/LoadingSpinner';

const PaymentSubmission = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const invoiceId = searchParams.get('invoice');
  
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [invoices, setInvoices] = useState([]);
  const [selectedInvoice, setSelectedInvoice] = useState(null);
  const [paymentSchedules, setPaymentSchedules] = useState([]);
  const [selectedSchedules, setSelectedSchedules] = useState([]);
  const [formData, setFormData] = useState({
    invoice_id: invoiceId || '',
    amount_paid: '',
    expected_amount: '',
    reference_number: '',
    payment_method: '',
    receipt_images: [],
    notes: ''
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    fetchInvoices();
    if (invoiceId) {
      fetchInvoiceDetails(invoiceId);
    }
  }, [invoiceId]);

  const fetchInvoices = async () => {
    try {
      console.log('Fetching invoices...');
      const response = await axiosClient.get('/customer/invoices');
      console.log('Invoices response:', response.data);
      const allInvoices = response.data.data || [];
      // Filter for unpaid and partially paid invoices
      const filteredInvoices = allInvoices.filter(invoice => 
        invoice.payment_status === 'unpaid' || invoice.payment_status === 'partially_paid'
      );
      console.log('Filtered invoices:', filteredInvoices);
      setInvoices(filteredInvoices);
    } catch (error) {
      console.error('Error fetching invoices:', error);
    }
  };

  const fetchPaymentSchedules = async (invoiceId) => {
    try {
      console.log('Fetching payment schedules for invoice:', invoiceId);
      const response = await axiosClient.get(`/customer/invoices/${invoiceId}/payment-schedules`);
      console.log('Payment schedules response:', response.data);
      setPaymentSchedules(response.data || []);
    } catch (error) {
      console.error('Error fetching payment schedules:', error);
      setPaymentSchedules([]);
    }
  };

  const fetchInvoiceDetails = async (id) => {
    try {
      console.log('Fetching invoice details for ID:', id);
      const response = await axiosClient.get(`/customer/invoices/${id}`);
      console.log('Invoice details response:', response.data);
      const invoice = response.data.data;
      setSelectedInvoice(invoice);
      setFormData(prev => ({
        ...prev,
        invoice_id: id,
        expected_amount: invoice.remaining_balance
      }));
      console.log('Selected invoice set:', invoice);
      
      // Fetch payment schedules for this invoice
      await fetchPaymentSchedules(id);
    } catch (error) {
      console.error('Error fetching invoice details:', error);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // If invoice selection changed, fetch invoice details and reset selections
    if (name === 'invoice_id' && value) {
      setSelectedSchedules([]);
      setFormData(prev => ({
        ...prev,
        amount_paid: ''
      }));
      fetchInvoiceDetails(value);
    }
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: null
      }));
    }
  };

  const handleFileChange = (e) => {
    const files = Array.from(e.target.files);
    setFormData(prev => ({
      ...prev,
      receipt_images: files
    }));
  };

  const handleScheduleSelection = (scheduleId, isChecked) => {
    let updatedSelectedSchedules;
    if (isChecked) {
      updatedSelectedSchedules = [...selectedSchedules, scheduleId];
    } else {
      updatedSelectedSchedules = selectedSchedules.filter(id => id !== scheduleId);
    }
    setSelectedSchedules(updatedSelectedSchedules);
    
    // Auto-compute amount based on selected schedules
    const totalAmount = paymentSchedules
      .filter(schedule => updatedSelectedSchedules.includes(schedule.id))
      .reduce((sum, schedule) => sum + parseFloat(schedule.amount || 0), 0);
    
    setFormData(prev => ({
      ...prev,
      amount_paid: totalAmount.toFixed(2)
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    try {
      const formDataToSend = new FormData();
      formDataToSend.append('invoice_id', formData.invoice_id);
      formDataToSend.append('amount_paid', formData.amount_paid);
      formDataToSend.append('expected_amount', formData.expected_amount);
      formDataToSend.append('reference_number', formData.reference_number);
      formDataToSend.append('payment_method', formData.payment_method);
      formDataToSend.append('notes', formData.notes);
      
      // Add selected payment schedules
      selectedSchedules.forEach((scheduleId, index) => {
        formDataToSend.append(`payment_schedules[${index}]`, scheduleId);
      });

      formData.receipt_images.forEach((file, index) => {
        formDataToSend.append(`receipt_images[${index}]`, file);
      });

      await axiosClient.post('/customer/payment-submission', formDataToSend, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      navigate('/payment-history', { 
        state: { message: 'Payment submission successful! You will receive an email notification once it is reviewed.' }
      });
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({ general: ['Payment submission failed. Please try again.'] });
      }
    } finally {
      setSubmitting(false);
    }
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

  // Debug logging
  console.log('PaymentSubmission state:', {
    invoices: invoices.length,
    selectedInvoice: selectedInvoice,
    formData: formData
  });

  return (
    <div className="container-fluid">
      <div className="row">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h3 mb-0" style={{ color: 'var(--text-color)' }}>
              <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-2 text-champagne" />
              Submit Payment
            </h1>
          </div>
        </div>
      </div>

      <div className="row">
        <div className="col-lg-8">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <h5 className="mb-0">Payment Submission Form</h5>
            </div>
            <div className="card-body">
              <form onSubmit={handleSubmit}>
                {/* Error Messages */}
                {Object.keys(errors).length > 0 && (
                  <div className="alert alert-danger">
                    <div className="d-flex align-items-center mb-2">
                      <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" />
                      <span className="fw-medium">Please fix the following errors:</span>
                    </div>
                    {Object.keys(errors).map(key => (
                      <div key={key} className="ms-4">• {errors[key]}</div>
                    ))}
                  </div>
                )}

                {/* Invoice Selection */}
                <div className="mb-4">
                  <label htmlFor="invoice_id" className="form-label fw-semibold">
                    Select Invoice *
                  </label>
                  <select
                    id="invoice_id"
                    name="invoice_id"
                    className="form-select"
                    value={formData.invoice_id}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="">Choose an invoice...</option>
                    {invoices.length > 0 ? (
                      invoices.map(invoice => (
                        <option key={invoice.id} value={invoice.id}>
                          {invoice.invoice_number} - {formatCurrency(invoice.remaining_balance)} remaining
                        </option>
                      ))
                    ) : (
                      <option value="" disabled>No unpaid invoices available</option>
                    )}
                  </select>
                  {invoices.length === 0 && (
                    <div className="form-text text-warning">
                      No unpaid or partially paid invoices found.
                    </div>
                  )}
                </div>

                {/* Selected Invoice Details */}
                {selectedInvoice && (
                  <div className="mb-4">
                    <div className="card border-info">
                      <div className="card-header bg-info text-white">
                        <h6 className="mb-0">Selected Invoice Details</h6>
                      </div>
                      <div className="card-body">
                        <div className="row">
                          <div className="col-md-6">
                            <p className="mb-1"><strong>Invoice #:</strong> {selectedInvoice.invoice_number}</p>
                            <p className="mb-1"><strong>Issue Date:</strong> {new Date(selectedInvoice.issue_date).toLocaleDateString()}</p>
                            <p className="mb-1"><strong>Due Date:</strong> {selectedInvoice.due_date ? new Date(selectedInvoice.due_date).toLocaleDateString() : 'N/A'}</p>
                          </div>
                          <div className="col-md-6">
                            <p className="mb-1"><strong>Total Amount:</strong> {formatCurrency(selectedInvoice.total_amount)}</p>
                            <p className="mb-1"><strong>Paid Amount:</strong> {formatCurrency(selectedInvoice.total_paid_amount)}</p>
                            <p className="mb-1"><strong>Remaining:</strong> {formatCurrency(selectedInvoice.remaining_balance)}</p>
                            {selectedInvoice.payment_term && (
                              <p className="mb-1"><strong>Payment Terms:</strong> {selectedInvoice.payment_term.name}</p>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Payment Amount */}
                <div className="row mb-4">
                  <div className="col-md-6">
                    <label htmlFor="amount_paid" className="form-label fw-semibold">
                      Amount Paid *
                    </label>
                    <div className="input-group">
                      <span className="input-group-text">₱</span>
                      <input
                        type="number"
                        id="amount_paid"
                        name="amount_paid"
                        className="form-control"
                        value={formData.amount_paid}
                        onChange={handleInputChange}
                        step="0.01"
                        min="0.01"
                        required
                        readOnly={selectedSchedules.length > 0}
                      />
                    </div>
                    {selectedSchedules.length > 0 && (
                      <div className="form-text text-info">
                        <FontAwesomeIcon icon={solidIconMap.infoCircle} className="me-1" />
                        Amount is auto-calculated based on selected payment schedules
                      </div>
                    )}
                  </div>
                  <div className="col-md-6">
                    <label htmlFor="expected_amount" className="form-label fw-semibold">
                      Expected Amount *
                    </label>
                    <div className="input-group">
                      <span className="input-group-text">₱</span>
                      <input
                        type="number"
                        id="expected_amount"
                        name="expected_amount"
                        className="form-control"
                        value={formData.expected_amount}
                        onChange={handleInputChange}
                        step="0.01"
                        min="0.01"
                        required
                      />
                    </div>
                  </div>
                </div>

                {/* Reference Number */}
                <div className="mb-4">
                  <label htmlFor="reference_number" className="form-label fw-semibold">
                    Reference Number *
                  </label>
                  <input
                    type="text"
                    id="reference_number"
                    name="reference_number"
                    className="form-control"
                    value={formData.reference_number}
                    onChange={handleInputChange}
                    placeholder="Enter payment reference number"
                    required
                  />
                </div>

                {/* Payment Method */}
                <div className="mb-4">
                  <label htmlFor="payment_method" className="form-label fw-semibold">
                    Payment Method *
                  </label>
                  <select
                    id="payment_method"
                    name="payment_method"
                    className="form-select"
                    value={formData.payment_method}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="">Select payment method...</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="debit_card">Debit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                {/* Payment Schedules Selection */}
                {selectedInvoice && paymentSchedules.length > 0 && (
                  <div className="mb-4">
                    <label className="form-label fw-semibold">
                      Select Payment Schedules *
                    </label>
                    <div className="table-responsive">
                      <table className="table table-bordered table-hover">
                        <thead className="table-light">
                          <tr>
                            <th width="50">Select</th>
                            <th>Schedule</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          {paymentSchedules.map((schedule, index) => (
                            <tr key={schedule.id} className={schedule.status === 'paid' ? 'table-success' : ''}>
                              <td>
                                <input
                                  type="checkbox"
                                  className="form-check-input"
                                  checked={selectedSchedules.includes(schedule.id)}
                                  disabled={schedule.status === 'paid'}
                                  onChange={(e) => handleScheduleSelection(schedule.id, e.target.checked)}
                                />
                              </td>
                              <td>
                                <strong>Payment {schedule.payment_order || index + 1}</strong>
                                {schedule.payment_type && (
                                  <div className="text-muted small">{schedule.payment_type}</div>
                                )}
                              </td>
                              <td>
                                {schedule.due_date ? new Date(schedule.due_date).toLocaleDateString() : 'N/A'}
                              </td>
                              <td>
                                <strong>{formatCurrency(schedule.amount || 0)}</strong>
                              </td>
                              <td>
                                <span className={`badge ${
                                  schedule.status === 'paid' ? 'bg-success' : 
                                  schedule.status === 'overdue' ? 'bg-danger' : 'bg-warning'
                                }`}>
                                  {schedule.status === 'paid' ? 'Paid' : 
                                   schedule.status === 'overdue' ? 'Overdue' : 'Pending'}
                                </span>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                    <div className="form-text">
                      <strong>Total Selected:</strong> {formatCurrency(
                        paymentSchedules
                          .filter(schedule => selectedSchedules.includes(schedule.id))
                          .reduce((sum, schedule) => sum + parseFloat(schedule.amount || 0), 0)
                      )}
                    </div>
                  </div>
                )}

                {/* Receipt Images */}
                <div className="mb-4">
                  <label htmlFor="receipt_images" className="form-label fw-semibold">
                    Receipt Images
                  </label>
                  <input
                    type="file"
                    id="receipt_images"
                    name="receipt_images"
                    className="form-control"
                    onChange={handleFileChange}
                    multiple
                    accept="image/*"
                  />
                  <div className="form-text">
                    Upload one or more receipt images (JPEG, PNG, JPG, GIF - Max 2MB each)
                  </div>
                </div>

                {/* Notes */}
                <div className="mb-4">
                  <label htmlFor="notes" className="form-label fw-semibold">
                    Additional Notes
                  </label>
                  <textarea
                    id="notes"
                    name="notes"
                    className="form-control"
                    rows="3"
                    value={formData.notes}
                    onChange={handleInputChange}
                    placeholder="Any additional information about this payment..."
                  />
                </div>

                {/* Submit Button */}
                <div className="d-flex gap-2">
                  <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={submitting}
                  >
                    {submitting ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                        Submitting...
                      </>
                    ) : (
                      <>
                        <FontAwesomeIcon icon={solidIconMap.paperPlane} className="me-2" />
                        Submit Payment
                      </>
                    )}
                  </button>
                  <button
                    type="button"
                    className="btn btn-outline-secondary"
                    onClick={() => navigate('/invoices')}
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        {/* Help Section */}
        <div className="col-lg-4">
          <div className="card shadow-sm">
            <div className="card-header bg-info text-white">
              <h5 className="mb-0">
                <FontAwesomeIcon icon={solidIconMap.infoCircle} className="me-2" />
                Payment Instructions
              </h5>
            </div>
            <div className="card-body">
              <ol className="mb-0">
                <li>Select the invoice you want to pay</li>
                <li>Choose payment schedules to pay (checkboxes)</li>
                <li>Amount will be auto-calculated</li>
                <li>Provide your payment reference number</li>
                <li>Select your payment method</li>
                <li>Upload receipt images as proof</li>
                <li>Add any additional notes if needed</li>
                <li>Submit for admin review</li>
              </ol>
            </div>
          </div>

          <div className="card shadow-sm mt-4">
            <div className="card-header bg-warning text-dark">
              <h5 className="mb-0">
                <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" />
                Important Notes
              </h5>
            </div>
            <div className="card-body">
              <ul className="mb-0">
                <li>Payment will be reviewed by admin before approval</li>
                <li>You will receive email notifications about status updates</li>
                <li>Keep your receipt images clear and readable</li>
                <li>Contact support if you have any questions</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentSubmission;
