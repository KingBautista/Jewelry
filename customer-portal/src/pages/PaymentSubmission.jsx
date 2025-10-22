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
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [formData, setFormData] = useState({
    invoice_id: invoiceId || '',
    reference_number: '',
    payment_method: '',
    receipt_images: [],
    notes: ''
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    fetchInvoices();
    fetchPaymentMethods();
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
      // Filter for unpaid, partially paid, and overdue invoices
      const filteredInvoices = allInvoices.filter(invoice => 
        invoice.payment_status === 'unpaid' || 
        invoice.payment_status === 'partially_paid' || 
        invoice.payment_status === 'overdue'
      );
      console.log('Filtered invoices:', filteredInvoices);
      setInvoices(filteredInvoices);
    } catch (error) {
      console.error('Error fetching invoices:', error);
    }
  };

  const fetchPaymentMethods = async () => {
    try {
      console.log('Fetching payment methods...');
      const response = await axiosClient.get('/options/payment-methods');
      console.log('Payment methods response:', response.data);
      setPaymentMethods(response.data || []);
    } catch (error) {
      console.error('Error fetching payment methods:', error);
    }
  };

  const fetchPaymentSchedules = async (invoiceId) => {
    try {
      console.log('Fetching payment schedules for invoice:', invoiceId);
      // Payment schedules are now included in the main invoice response
      // No need for separate API call
      if (selectedInvoice && selectedInvoice.payment_schedules) {
        setPaymentSchedules(selectedInvoice.payment_schedules);
      } else {
        setPaymentSchedules([]);
      }
    } catch (error) {
      console.error('Error processing payment schedules:', error);
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
        invoice_id: id
      }));
      console.log('Selected invoice set:', invoice);
      
      // Payment schedules are now included in the invoice response
      if (invoice.payment_schedules) {
        setPaymentSchedules(invoice.payment_schedules);
      } else {
        setPaymentSchedules([]);
      }
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
    
    // No need to update formData since we removed amount_paid field
  };

  const handleSelectAll = (isChecked) => {
    if (isChecked) {
      // Select all non-paid schedules
      const availableSchedules = paymentSchedules
        .filter(schedule => schedule.status !== 'paid')
        .map(schedule => schedule.id);
      setSelectedSchedules(availableSchedules);
      
      // Auto-compute amount for all selected
      const totalAmount = paymentSchedules
        .filter(schedule => availableSchedules.includes(schedule.id))
        .reduce((sum, schedule) => sum + parseFloat(schedule.amount || 0), 0);
      
      // No need to update formData since we removed amount_paid field
    } else {
      // Deselect all
      setSelectedSchedules([]);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});

    // Validate payment term selection
    if (selectedSchedules.length === 0) {
      setErrors({ payment_terms: ['Please select at least one payment term to pay.'] });
      setSubmitting(false);
      return;
    }

    try {
      // Calculate amount from selected schedules
      const selectedAmount = paymentSchedules
        .filter(schedule => selectedSchedules.includes(schedule.id))
        .reduce((sum, schedule) => sum + parseFloat(schedule.amount || 0), 0);

      const formDataToSend = new FormData();
      formDataToSend.append('invoice_id', formData.invoice_id);
      formDataToSend.append('amount_paid', selectedAmount.toFixed(2));
      formDataToSend.append('expected_amount', selectedAmount.toFixed(2));
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
                      <option value="" disabled>No invoices available for payment</option>
                    )}
                  </select>
                  {invoices.length === 0 && (
                    <div className="form-text text-warning">
                      No unpaid, partially paid, or overdue invoices found.
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

                {/* Payment Schedules Selection */}
                {selectedInvoice && paymentSchedules.length > 0 && (
                  <div className="mb-4">
                    <label className="form-label fw-semibold">
                      Select Payment Terms to Pay *
                    </label>
                    <div className="alert alert-info">
                      <FontAwesomeIcon icon={solidIconMap.infoCircle} className="me-2" />
                      <strong>Flexible Payment:</strong> You can select multiple payment terms to pay at once. 
                      Paid schedules are automatically excluded from selection.
                    </div>
                    <div className="table-responsive">
                      <table className="table table-bordered table-hover">
                        <thead className="table-light d-none d-md-table-header-group">
                          <tr>
                            <th width="50">
                              <input
                                type="checkbox"
                                className="form-check-input"
                                checked={selectedSchedules.length === paymentSchedules.filter(s => s.status !== 'paid').length && paymentSchedules.filter(s => s.status !== 'paid').length > 0}
                                onChange={(e) => handleSelectAll(e.target.checked)}
                              />
                            </th>
                            <th>Payment Term</th>
                            <th>Type</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          {paymentSchedules
                            .sort((a, b) => a.payment_order - b.payment_order)
                            .map((schedule, index) => (
                            <tr key={schedule.id} className={schedule.status === 'paid' ? 'table-success' : schedule.status === 'overdue' ? 'table-danger' : ''}>
                              {/* Mobile card layout */}
                              <td className="d-md-none">
                                <div className="card border-0 bg-light">
                                  <div className="card-body p-3">
                                    <div className="d-flex justify-content-between align-items-start mb-2">
                                      <div className="form-check">
                                        <input
                                          type="checkbox"
                                          className="form-check-input"
                                          checked={selectedSchedules.includes(schedule.id)}
                                          disabled={schedule.status === 'paid'}
                                          onChange={(e) => handleScheduleSelection(schedule.id, e.target.checked)}
                                        />
                                        <label className="form-check-label fw-semibold">
                                          Payment {schedule.payment_order}
                                        </label>
                                      </div>
                                      <span className={`badge ${
                                        schedule.status === 'paid' ? 'bg-success' : 
                                        schedule.status === 'overdue' ? 'bg-danger' : 'bg-warning'
                                      }`}>
                                        {schedule.status === 'paid' ? 'Paid' : 
                                         schedule.status === 'overdue' ? 'Overdue' : 'Pending'}
                                      </span>
                                    </div>
                                    <div className="d-flex justify-content-between align-items-center mb-2">
                                      <div>
                                        <span className="badge bg-primary">{schedule.payment_type}</span>
                                        {schedule.due_date && (
                                          <div className="text-muted small mt-1">
                                            Due: {new Date(schedule.due_date).toLocaleDateString()}
                                          </div>
                                        )}
                                      </div>
                                      <div className="text-end">
                                        <div className="fw-semibold text-primary">{formatCurrency(schedule.amount || 0)}</div>
                                        {schedule.paid_amount > 0 && (
                                          <div className="text-success small">
                                            Paid: {formatCurrency(schedule.paid_amount)}
                                          </div>
                                        )}
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </td>
                              
                              {/* Desktop table layout */}
                              <td className="d-none d-md-table-cell">
                                <input
                                  type="checkbox"
                                  className="form-check-input"
                                  checked={selectedSchedules.includes(schedule.id)}
                                  disabled={schedule.status === 'paid'}
                                  onChange={(e) => handleScheduleSelection(schedule.id, e.target.checked)}
                                />
                              </td>
                              <td className="d-none d-md-table-cell">
                                <strong>Payment {schedule.payment_order}</strong>
                                {schedule.payment_type && (
                                  <div className="text-muted small">
                                    <FontAwesomeIcon icon={solidIconMap.calendar} className="me-1" />
                                    {schedule.payment_type}
                                  </div>
                                )}
                              </td>
                              <td className="d-none d-md-table-cell">
                                <span className="badge bg-primary">{schedule.payment_type}</span>
                              </td>
                              <td className="d-none d-md-table-cell">
                                {schedule.due_date ? (
                                  <div>
                                    <div>{new Date(schedule.due_date).toLocaleDateString()}</div>
                                    <div className="text-muted small">
                                      {new Date(schedule.due_date).toLocaleDateString('en-US', { 
                                        weekday: 'short', 
                                        month: 'short', 
                                        day: 'numeric' 
                                      })}
                                    </div>
                                  </div>
                                ) : 'N/A'}
                              </td>
                              <td className="d-none d-md-table-cell">
                                <strong className="text-primary">{formatCurrency(schedule.amount || 0)}</strong>
                                {schedule.paid_amount > 0 && (
                                  <div className="text-success small">
                                    Paid: {formatCurrency(schedule.paid_amount)}
                                  </div>
                                )}
                              </td>
                              <td className="d-none d-md-table-cell">
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
                    
                    {/* Selection Summary */}
                    <div className="row mt-3">
                      <div className="col-md-6">
                        <div className="card border-primary">
                          <div className="card-body">
                            <h6 className="card-title text-primary">
                              <FontAwesomeIcon icon={solidIconMap.calculator} className="me-2" />
                              Selected Amount
                            </h6>
                            <p className="card-text h5 mb-0">
                              {formatCurrency(
                                paymentSchedules
                                  .filter(schedule => selectedSchedules.includes(schedule.id))
                                  .reduce((sum, schedule) => sum + parseFloat(schedule.amount || 0), 0)
                              )}
                            </p>
                          </div>
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="card border-info">
                          <div className="card-body">
                            <h6 className="card-title text-info">
                              <FontAwesomeIcon icon={solidIconMap.list} className="me-2" />
                              Selection Summary
                            </h6>
                            <p className="card-text mb-0">
                              <strong>{selectedSchedules.length}</strong> payment term(s) selected
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

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
                    {paymentMethods.length > 0 ? (
                      paymentMethods.map(paymentMethod => (
                        <option key={paymentMethod.id} value={paymentMethod.id}>
                          {paymentMethod.bank_name} - {paymentMethod.account_name}
                          {paymentMethod.account_number && ` (${paymentMethod.account_number})`}
                        </option>
                      ))
                    ) : (
                      <>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                      </>
                    )}
                  </select>
                  {paymentMethods.length === 0 && (
                    <div className="form-text text-warning">
                      <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-1" />
                      No payment methods available. Using default options.
                    </div>
                  )}
                </div>

                {/* Payment Method Details */}
                {formData.payment_method && paymentMethods.length > 0 && (
                  <div className="mb-4">
                    {(() => {
                      const selectedPaymentMethod = paymentMethods.find(pm => pm.id == formData.payment_method);
                      return selectedPaymentMethod ? (
                        <div className="card border-primary">
                          <div className="card-header bg-primary text-white">
                            <h6 className="mb-0">
                              <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-2" />
                              Selected Payment Method
                            </h6>
                          </div>
                          <div className="card-body">
                            <div className="row">
                              {/* Payment Method Information */}
                              <div className="col-md-6">
                                <h6 className="fw-semibold text-primary mb-3">Payment Details</h6>
                                <div className="mb-2">
                                  <strong>Bank:</strong> {selectedPaymentMethod.bank_name}
                                </div>
                                <div className="mb-2">
                                  <strong>Account Name:</strong> {selectedPaymentMethod.account_name}
                                </div>
                                {selectedPaymentMethod.account_number && (
                                  <div className="mb-2">
                                    <strong>Account Number:</strong> {selectedPaymentMethod.account_number}
                                  </div>
                                )}
                                {selectedPaymentMethod.description && (
                                  <div className="mb-2">
                                    <strong>Description:</strong> {selectedPaymentMethod.description}
                                  </div>
                                )}
                                <div className="mt-3">
                                  <span className="badge bg-success">
                                    <FontAwesomeIcon icon={solidIconMap.checkCircle} className="me-1" />
                                    Active Payment Method
                                  </span>
                                </div>
                              </div>
                              
                              {/* QR Code Section */}
                              <div className="col-md-6">
                                {selectedPaymentMethod.qr_code_url ? (
                                  <div>
                                    <h6 className="fw-semibold text-primary mb-3">
                                      <FontAwesomeIcon icon={solidIconMap.qrcode} className="me-2" />
                                      QR Code Payment
                                    </h6>
                                    <div className="text-center">
                                      <p className="text-muted mb-3">
                                        Scan this QR code to make payment directly
                                      </p>
                                      <img
                                        src={selectedPaymentMethod.qr_code_url}
                                        alt="Payment QR Code"
                                        className="img-thumbnail border-2"
                                        style={{ 
                                          maxWidth: '200px', 
                                          maxHeight: '200px',
                                          borderColor: '#0d6efd !important'
                                        }}
                                      />
                                      <div className="mt-3">
                                        <small className="text-success">
                                          <FontAwesomeIcon icon={solidIconMap.mobile} className="me-1" />
                                          Mobile Payment Ready
                                        </small>
                                      </div>
                                    </div>
                                  </div>
                                ) : (
                                  <div className="text-center">
                                    <h6 className="fw-semibold text-muted mb-3">
                                      <FontAwesomeIcon icon={solidIconMap.creditCard} className="me-2" />
                                      Manual Payment
                                    </h6>
                                    <p className="text-muted">
                                      Use the account details above to make your payment manually.
                                    </p>
                                    <div className="alert alert-info">
                                      <FontAwesomeIcon icon={solidIconMap.infoCircle} className="me-2" />
                                      <strong>Note:</strong> Please include your reference number when making the payment.
                                    </div>
                                  </div>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>
                      ) : null;
                    })()}
                  </div>
                )}

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
                <li><strong>Choose payment terms:</strong> Select one or multiple payment terms using checkboxes</li>
                <li><strong>Flexible selection:</strong> You can pay multiple terms at once (e.g., 2 months together)</li>
                <li><strong>Amount is auto-calculated:</strong> Based on your selected payment terms</li>
                <li>Provide your payment reference number</li>
                <li><strong>Select payment method:</strong> Choose from available payment methods or use default options</li>
                <li><strong>QR Code payment:</strong> If available, scan the QR code to make payment directly</li>
                <li>Upload receipt images as proof of payment</li>
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
