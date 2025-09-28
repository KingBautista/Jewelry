import { useEffect, useRef, useState, useCallback } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';
import debounce from 'lodash/debounce';

export default function PaymentForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Payment');
  const [payment, setPayment] = useState({
    id: null,
    invoice_id: '',
    customer_id: '',
    payment_type: '',
    payment_method_id: '',
    amount_paid: '',
    expected_amount: '',
    reference_number: '',
    receipt_image: '',
    status: 'pending',
    rejection_reason: '',
    payment_date: '',
    notes: '',
  });
  const [isLoading, setIsLoading] = useState(false);
  const [selectedReceiptFiles, setSelectedReceiptFiles] = useState([]);
  
  // Check if form should be read-only
  const isReadOnly = payment.status === 'confirmed';
  
  // Searchable invoice dropdown states
  const [invoiceSearchResults, setInvoiceSearchResults] = useState([]);
  const [invoiceSearchTerm, setInvoiceSearchTerm] = useState('');
  const [isInvoiceSearching, setIsInvoiceSearching] = useState(false);
  const [selectedInvoice, setSelectedInvoice] = useState(null);
  const [showInvoiceDropdownUp, setShowInvoiceDropdownUp] = useState(false);
  
  // Dropdown data
  const [invoices, setInvoices] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [paymentMethods, setPaymentMethods] = useState([]);

  // Payment schedule selection
  const [selectedSchedules, setSelectedSchedules] = useState([]);

  // Refs
  const invoiceSearchInputRef = useRef(null);

  // Debounced search function for invoices
  const debouncedInvoiceSearch = useRef(
    debounce((term) => {
      if (term.length >= 3) {
        setIsInvoiceSearching(true);
        axiosClient.get(`/options/invoices/search?search=${term}`)
          .then(({ data }) => {
            setInvoiceSearchResults(data);
            setIsInvoiceSearching(false);
          })
          .catch((error) => {
            console.error('Error searching invoices:', error);
            setIsInvoiceSearching(false);
            setInvoiceSearchResults([]);
            
            // Show error message for search
            if (error.response?.status === 401) {
              toastAction.current.showError('Authentication required. Please log in again.');
            } else if (error.response?.status === 403) {
              toastAction.current.showError('Access denied. You do not have permission to search invoices.');
            } else if (error.response?.status >= 500) {
              toastAction.current.showError('Server error while searching invoices. Please try again later.');
            } else {
              toastAction.current.showError('Failed to search invoices. Please try again.');
            }
          });
      } else {
        setInvoiceSearchResults([]);
      }
    }, 300)
  ).current;

  // Check if dropdown should show upward
  const checkInvoiceDropdownPosition = () => {
    if (invoiceSearchInputRef.current) {
      const inputRect = invoiceSearchInputRef.current.getBoundingClientRect();
      const spaceBelow = window.innerHeight - inputRect.bottom;
      const spaceAbove = inputRect.top;
      const dropdownHeight = 300; // Approximate max height of dropdown

      setShowInvoiceDropdownUp(spaceBelow < dropdownHeight && spaceAbove > spaceBelow);
    }
  };

  // Handle invoice search input change
  const handleInvoiceSearchChange = (e) => {
    const value = e.target.value;
    setInvoiceSearchTerm(value);
    debouncedInvoiceSearch(value);
    checkInvoiceDropdownPosition();
  };

  // Handle invoice selection
  const handleInvoiceSelect = (invoice) => {
    setPayment(prev => ({
      ...prev,
      invoice_id: invoice.id,
      customer_id: invoice.customer?.id || '',
      expected_amount: invoice.payment_schedules?.find(s => s.status === 'pending')?.expected_amount || invoice.total_amount
    }));
    setSelectedInvoice(invoice);
    setInvoiceSearchTerm(`${invoice.invoice_number} - ${invoice.product_name || 'Product/Service'} (${invoice.total_amount || '₱0.00'})`);
    setInvoiceSearchResults([]);
    // Reset selected schedules when new invoice is selected
    setSelectedSchedules([]);
  };

  // Handle payment schedule selection
  const handleScheduleSelect = (schedule, isSelected) => {
    // Don't allow selection/deselection of paid schedules
    if (schedule.status === 'paid') {
      return;
    }
    
    let newSelectedSchedules;
    if (isSelected) {
      newSelectedSchedules = [...selectedSchedules, schedule];
    } else {
      newSelectedSchedules = selectedSchedules.filter(s => s.id !== schedule.id);
    }
    
    setSelectedSchedules(newSelectedSchedules);
    
    // Auto-compute paid amount
    const totalAmount = newSelectedSchedules.reduce((sum, s) => sum + parseFloat(s.expected_amount), 0);
    
    // Generate notes
    const scheduleNotes = newSelectedSchedules
      .sort((a, b) => a.payment_order - b.payment_order)
      .map(s => `${s.payment_type} (Order ${s.payment_order}) - ₱${parseFloat(s.expected_amount).toFixed(2)}`)
      .join(', ');
    
    setPayment(prev => ({
      ...prev,
      amount_paid: totalAmount.toFixed(2),
      expected_amount: totalAmount.toFixed(2),
      notes: scheduleNotes ? `Payment for: ${scheduleNotes}` : prev.notes
    }));
  };

  // Handle select all schedules
  const handleSelectAllSchedules = (isSelected) => {
    if (!selectedInvoice?.payment_schedules) return;
    
    const pendingSchedules = selectedInvoice.payment_schedules.filter(s => s.status === 'pending');
    
    if (isSelected) {
      setSelectedSchedules(pendingSchedules);
      const totalAmount = pendingSchedules.reduce((sum, s) => sum + parseFloat(s.expected_amount), 0);
      const scheduleNotes = pendingSchedules
        .sort((a, b) => a.payment_order - b.payment_order)
        .map(s => `${s.payment_type} (Order ${s.payment_order}) - ₱${parseFloat(s.expected_amount).toFixed(2)}`)
        .join(', ');
      
      setPayment(prev => ({
        ...prev,
        amount_paid: totalAmount.toFixed(2),
        expected_amount: totalAmount.toFixed(2),
        notes: `Payment for: ${scheduleNotes}`
      }));
    } else {
      setSelectedSchedules([]);
      setPayment(prev => ({
        ...prev,
        amount_paid: '',
        expected_amount: '',
        notes: ''
      }));
    }
  };

  // Handle receipt file selection
  const handleReceiptFileSelect = (event) => {
    const files = Array.from(event.target.files);
    setSelectedReceiptFiles(files);
    
    // Create preview URLs for display
    const previewUrls = files.map(file => URL.createObjectURL(file));
    setPayment({ 
      ...payment, 
      receipt_image: previewUrls[0] || '' // Use first image as main receipt
    });
  };

  // Handle remove receipt image
  const handleRemoveReceiptImage = () => {
    setPayment({ ...payment, receipt_image: '' });
    setSelectedReceiptFiles([]);
  };

  // Load dropdown data
  useEffect(() => {
    const loadDropdownData = async () => {
      try {
        const [invoicesRes, customersRes, paymentMethodsRes] = await Promise.all([
          axiosClient.get('/options/invoices'),
          axiosClient.get('/options/customers'),
          axiosClient.get('/options/payment-methods')
        ]);

        setInvoices(invoicesRes.data || []);
        setCustomers(customersRes.data || []);
        setPaymentMethods(paymentMethodsRes.data || []);
      } catch (error) {
        console.error('Error loading dropdown data:', error);
        
        // Show specific error message for invoices
        if (error.response?.status === 401) {
          toastAction.current.showError('Authentication required. Please log in again.');
        } else if (error.response?.status === 403) {
          toastAction.current.showError('Access denied. You do not have permission to access this resource.');
        } else if (error.response?.status >= 500) {
          toastAction.current.showError('Server error. Please try again later.');
        } else {
          toastAction.current.showError('Failed to load dropdown data. Please refresh the page.');
        }
      }
    };

    loadDropdownData();
  }, []);

  // Load payment data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/payment-management/payments/${id}`)
        .then(({ data }) => {
          const paymentData = data.data || data;
          
          // Convert payment_date to YYYY-MM-DD format for date input
          if (paymentData.payment_date) {
            const date = new Date(paymentData.payment_date);
            paymentData.payment_date = date.toISOString().split('T')[0];
          }
          
          setPayment(paymentData);
          
          // Handle receipt images if they exist
          if (paymentData.receipt_images && paymentData.receipt_images.length > 0) {
            // Set the first image as the primary receipt image for display
            setPayment(prev => ({
              ...prev,
              receipt_image: paymentData.receipt_images[0]
            }));
          }
          
          // If payment has an invoice, load invoice details
          if (paymentData.invoice_id) {
            // Check if invoice data is already included in the payment response
            if (paymentData.invoice) {
              // Add payment schedules to the invoice data if they exist on the payment
              const invoiceWithSchedules = {
                ...paymentData.invoice,
                payment_schedules: paymentData.payment_schedules || paymentData.invoice.payment_schedules || []
              };
              
              setSelectedInvoice(invoiceWithSchedules);
              setInvoiceSearchTerm(`${paymentData.invoice.invoice_number} - ${paymentData.invoice.product_name || 'Product/Service'} (${paymentData.invoice.total_amount || '₱0.00'})`);
              
              // Load paid schedules for this payment
              if (paymentData.paid_schedules) {
                setSelectedSchedules(paymentData.paid_schedules);
              }
            } else {
              // First try to get the invoice directly by ID
              axiosClient.get(`/options/invoices/${paymentData.invoice_id}`)
                .then(({ data: invoiceData }) => {
                  if (invoiceData) {
                    setSelectedInvoice(invoiceData);
                    setInvoiceSearchTerm(`${invoiceData.invoice_number} - ${invoiceData.product_name} (${invoiceData.total_amount})`);
                  }
                })
                .catch(() => {
                  // If direct fetch fails, try search method
                  axiosClient.get(`/options/invoices/search?search=${paymentData.invoice_id}`)
                    .then(({ data: invoiceData }) => {
                      if (invoiceData && invoiceData.length > 0) {
                        const invoice = invoiceData.find(inv => inv.id === paymentData.invoice_id);
                        if (invoice) {
                          setSelectedInvoice(invoice);
                          setInvoiceSearchTerm(`${invoice.invoice_number} - ${invoice.product_name || 'Product/Service'} (${invoice.total_amount || '₱0.00'})`);
                        }
                      }
                    })
                    .catch(() => {
                      // If both methods fail, try searching by invoice number if available
                      if (paymentData.invoice?.invoice_number) {
                        axiosClient.get(`/options/invoices/search?search=${paymentData.invoice.invoice_number}`)
                          .then(({ data: invoiceData }) => {
                            if (invoiceData && invoiceData.length > 0) {
                              const invoice = invoiceData.find(inv => inv.invoice_number === paymentData.invoice.invoice_number);
                              if (invoice) {
                                setSelectedInvoice(invoice);
                                setInvoiceSearchTerm(`${invoice.invoice_number} - ${invoice.product_name || 'Product/Service'} (${invoice.total_amount || '₱0.00'})`);
                              }
                            }
                          })
                          .catch(() => {
                            // Last resort: show basic info
                            setInvoiceSearchTerm(`Invoice ID: ${paymentData.invoice_id}`);
                          });
                      } else {
                        // Last resort: show basic info
                        setInvoiceSearchTerm(`Invoice ID: ${paymentData.invoice_id}`);
                      }
                    });
                });
            }
          }
          
          setIsLoading(false);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  }, [id]);

  // Handle form submission
  const onSubmit = (ev) => {
    ev.preventDefault();
    
    setIsLoading(true);

    // Create FormData for file uploads
    const formData = new FormData();
    
    // Add all payment data with proper formatting - only append if value exists
    if (payment.invoice_id) formData.append('invoice_id', payment.invoice_id);
    if (payment.customer_id) formData.append('customer_id', payment.customer_id);
    if (payment.payment_type) formData.append('payment_type', payment.payment_type);
    if (payment.payment_method_id) formData.append('payment_method_id', payment.payment_method_id);
    if (payment.amount_paid) formData.append('amount_paid', parseFloat(payment.amount_paid) || 0);
    if (payment.expected_amount) formData.append('expected_amount', parseFloat(payment.expected_amount) || 0);
    if (payment.reference_number) formData.append('reference_number', payment.reference_number);
    if (payment.status) formData.append('status', payment.status);
    if (payment.rejection_reason) formData.append('rejection_reason', payment.rejection_reason);
    if (payment.payment_date) formData.append('payment_date', payment.payment_date);
    if (payment.notes) formData.append('notes', payment.notes);
    
    // Add payment ID for updates
    if (payment.id) {
      formData.append('payment_id', payment.id);
    }
    
    // Add selected receipt files
    selectedReceiptFiles.forEach((file, index) => {
      formData.append(`receipt_images[${index}]`, file);
    });
    
    // Add selected payment schedules
    selectedSchedules.forEach((schedule, index) => {
      formData.append(`selected_schedules[${index}]`, schedule.id);
    });

    // Let axios handle the Content-Type header automatically
    const config = {};

    // Use POST for both create and update to avoid FormData parsing issues with PUT
    const request = axiosClient.post('/payment-management/payments', formData, config);

    request
      .then(() => {
        const action = payment.id ? 'updated' : 'added';
        toastAction.current.showToast(`Payment has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/payment-management/payments'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!payment.id) return;
    
    if (window.confirm('Are you sure you want to delete this payment?')) {
      setIsLoading(true);
      axiosClient.delete(`/payment-management/payments/${payment.id}`)
        .then(() => {
          toastAction.current.showToast('Payment has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/payment-management/payments'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle approve payment
  const handleApprove = () => {
    if (!payment.id) return;
    
    if (window.confirm('Are you sure you want to approve this payment?')) {
      setIsLoading(true);
      axiosClient.patch(`/payment-management/payments/${payment.id}/approve`)
        .then(() => {
          toastAction.current.showToast('Payment has been approved.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/payment-management/payments'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle confirm payment
  const handleConfirm = () => {
    if (!payment.id) return;
    
    if (window.confirm('Are you sure you want to confirm this payment?')) {
      setIsLoading(true);
      
      // Send selected schedules with the confirm request
      const confirmData = {
        selected_schedules: selectedSchedules.map(schedule => schedule.id)
      };
      
      axiosClient.patch(`/payment-management/payments/${payment.id}/confirm`, confirmData)
        .then(() => {
          toastAction.current.showToast('Payment has been confirmed.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/payment-management/payments'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle reject payment
  const handleReject = () => {
    if (!payment.id) return;
    
    const reason = window.prompt('Please enter the rejection reason:');
    if (reason) {
      setIsLoading(true);
      axiosClient.patch(`/payment-management/payments/${payment.id}/reject`, {
        rejection_reason: reason
      })
        .then(() => {
          toastAction.current.showToast('Payment has been rejected.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/payment-management/payments'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle send update invoice
  const handleSendUpdateInvoice = () => {
    if (!payment.id || !selectedInvoice) return;
    
    if (window.confirm('Are you sure you want to send an updated invoice email to the customer?')) {
      setIsLoading(true);
      axiosClient.post(`/payment-management/payments/${payment.id}/send-update-invoice`)
        .then(() => {
          toastAction.current.showToast('Updated invoice has been sent to the customer.', 'success');
          setIsLoading(false);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  return (
    <>
    <div className="card">
      <form onSubmit={onSubmit}>
        <div className="card-header">
          <div className="d-flex justify-content-between align-items-center">
            <h4 className="mb-0">
              {payment.id ? 'Edit Payment' : 'Create New Payment'}
            </h4>
            {isReadOnly && <span className="badge text-bg-success">READ ONLY</span>}
          </div>
          {!payment.id && <p className="tip-message">Create a new payment record for your jewelry business.</p>}
          {isReadOnly && <p className="tip-message text-dark">This payment has been confirmed and cannot be edited.</p>}
        </div>
        <div className="card-body">
          {/* Invoice Search Field */}
          <Field
            label="Invoice"
            required={true}
            inputComponent={
              <div className="position-relative">
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by invoice number, product name, or customer name..."
                  value={invoiceSearchTerm}
                  onChange={handleInvoiceSearchChange}
                  ref={invoiceSearchInputRef}
                  required
                  disabled={isReadOnly}
                />
                <div className="form-text">
                  <small className="text-dark">
                    <FontAwesomeIcon icon={solidIconMap.info} className="me-1" />
                    Type at least 3 characters to search invoices by number, product name, or customer name
                  </small>
                </div>
                {isInvoiceSearching && (
                  <div className="position-absolute top-100 start-0 w-100 bg-body border rounded-bottom p-2">
                    <div className="text-center">
                      <div className="spinner-border spinner-border-sm" role="status">
                        <span className="visually-hidden">Loading...</span>
                      </div>
                    </div>
                  </div>
                )}
                {invoiceSearchResults.length > 0 && !isInvoiceSearching && (
                  <div className="position-absolute w-100 bg-body border rounded-bottom shadow-sm" 
                    style={{ 
                      [showInvoiceDropdownUp ? 'bottom' : 'top']: '100%',
                      left: 0,
                      zIndex: 1000,
                      maxHeight: 'calc(100vh - 300px)', 
                      overflowY: 'auto'
                    }}>
                    {invoiceSearchResults.map((invoice) => (
                      <div
                        key={invoice.id}
                        className="p-2 cursor-pointer text-body"
                        onClick={() => handleInvoiceSelect(invoice)}
                        style={{ 
                          cursor: 'pointer',
                          transition: 'background-color 0.15s ease-in-out'
                        }}
                        onMouseEnter={(e) => {
                          e.target.style.backgroundColor = 'var(--bs-secondary-bg)';
                        }}
                        onMouseLeave={(e) => {
                          e.target.style.backgroundColor = 'transparent';
                        }}>
                        {invoice.invoice_number} - {invoice.product_name || 'Product/Service'} ({invoice.total_amount || '₱0.00'})
                      </div>
                    ))}
                  </div>
                )}
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Invoice Information Display */}
          {selectedInvoice && (
            <div className="row mb-4">
              <div className="col-3">Invoice Information</div>
              <div className="col-9">
                <div className="card">
                  <div className="card-body">
                    <div className="row">
                      <div className="col-md-6">
                        <div className="mb-2">
                          <strong>Invoice Number:</strong> {selectedInvoice.invoice_number}
                        </div>
                        <div className="mb-2">
                          <strong>Product:</strong> {selectedInvoice.product_name || 'Product/Service'}
                        </div>
                        <div className="mb-2">
                          <strong>Total Amount:</strong> {selectedInvoice.total_amount}
                        </div>
                        <div className="mb-2">
                          <strong>Payment Status:</strong> 
                          <span className={`badge ms-2 ${
                            selectedInvoice.payment_status === 'fully_paid' ? 'text-bg-success' :
                            selectedInvoice.payment_status === 'partially_paid' ? 'text-bg-warning' :
                            selectedInvoice.payment_status === 'overdue' ? 'text-bg-danger' :
                            'text-bg-secondary'
                          }`}>
                            {selectedInvoice.payment_status?.replace('_', ' ').toUpperCase()}
                          </span>
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="mb-2">
                          <strong>Total Paid:</strong> {selectedInvoice.total_paid_amount}
                        </div>
                        <div className="mb-2">
                          <strong>Remaining Balance:</strong> {selectedInvoice.remaining_balance}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Customer Information Display */}
          {selectedInvoice?.customer && (
            <div className="row mb-4">
              <div className="col-3">Customer Information</div>
              <div className="col-9">
                <div className="card">
                  <div className="card-body">
                    <div className="row">
                      <div className="col-md-6">
                        <div className="mb-2">
                          <strong>Name:</strong> {selectedInvoice.customer.full_name || selectedInvoice.customer.name}
                        </div>
                        <div className="mb-2">
                          <strong>Email:</strong> {selectedInvoice.customer.user_email || selectedInvoice.customer.email}
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="mb-2">
                          <strong>Phone:</strong> {selectedInvoice.customer.formatted_phone || selectedInvoice.customer.phone}
                        </div>
                        <div className="mb-2">
                          <strong>Address:</strong> {selectedInvoice.customer.formatted_address || selectedInvoice.customer.address}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Payment Term Information Display */}
          {selectedInvoice?.payment_term && (
            <div className="row mb-4">
              <div className="col-3">Payment Term Information</div>
              <div className="col-9">
                <div className="card">
                  <div className="card-body">
                    <div className="row">
                      <div className="col-md-6">
                        <div className="mb-2">
                          <strong>Payment Term:</strong> {selectedInvoice.payment_term.name}
                        </div>
                        <div className="mb-2">
                          <strong>Code:</strong> {selectedInvoice.payment_term.code}
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="mb-2">
                          <strong>Down Payment:</strong> {selectedInvoice.payment_term.down_payment_percentage}%
                        </div>
                        <div className="mb-2">
                          <strong>Term Duration:</strong> {selectedInvoice.payment_term.term_months} months
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Payment Schedule Display */}
          {selectedInvoice?.payment_schedules && selectedInvoice.payment_schedules.length > 0 && (
            <div className="row mb-4">
              <div className="col-3">Payment Schedule <br/>                
                <small className="text-dark">
                  <FontAwesomeIcon icon={solidIconMap.info} className="me-1" />
                  Paid schedules are automatically checked and disabled
                </small>
              </div>
              <div className="col-9">
                <div className="table-responsive" style={{ height: 'auto', maxHeight: 'none', overflow: 'visible' }}>
                  <table className="table table-sm table-hover" style={{ whiteSpace: 'nowrap' }}>
                    <thead className="table-light">
                      <tr>
                        <th width="5%">
                          <div className="form-check d-flex justify-content-center">
                            <input
                              className="form-check-input"
                              type="checkbox"
                              checked={selectedInvoice?.payment_schedules && 
                                selectedInvoice.payment_schedules.filter(s => s.status === 'pending').length > 0 &&
                                selectedInvoice.payment_schedules.filter(s => s.status === 'pending').every(s => 
                                  selectedSchedules.some(selected => selected.id === s.id)
                                )}
                              onChange={(e) => handleSelectAllSchedules(e.target.checked)}
                              disabled={isReadOnly}
                            />
                          </div>
                        </th>
                        <th width="20%">Payment Type</th>
                        <th width="20%">Due Date</th>
                        <th width="20%">Expected Amount</th>
                        <th width="15%">Status</th>
                        <th width="10%">Order</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedInvoice.payment_schedules
                        .sort((a, b) => a.payment_order - b.payment_order)
                        .map((schedule) => (
                        <tr key={schedule.id}>
                          <td>
                            <div className="form-check d-flex justify-content-center">
                              <input
                                className="form-check-input"
                                type="checkbox"
                                checked={selectedSchedules.some(selected => selected.id === schedule.id)}
                                disabled={schedule.status === 'paid' || isReadOnly}
                                onChange={(e) => handleScheduleSelect(schedule, e.target.checked)}
                              />
                            </div>
                          </td>
                          <td>
                            <span className="badge text-bg-primary me-2">{schedule.payment_order}</span>&nbsp;
                            {schedule.payment_type}
                          </td>
                          <td>{new Date(schedule.due_date).toLocaleDateString()}</td>
                          <td>₱{parseFloat(schedule.expected_amount).toFixed(2)}</td>
                          <td>
                            <span className={`badge ${
                              schedule.status === 'paid' ? 'text-bg-success' :
                              schedule.status === 'pending' ? 'text-bg-warning' :
                              'text-bg-secondary'
                            }`}>
                              {schedule.status.toUpperCase()}
                            </span>
                          </td>
                          <td>{schedule.payment_order}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}


          {/* Payment Type Field */}
          <Field
            label="Payment Type"
            required={true}
            inputComponent={
              <select
                className="form-select"
                value={payment.payment_type || ''}
                onChange={ev => setPayment({ ...payment, payment_type: ev.target.value })}
                required
                disabled={isReadOnly}
              >
                <option value="">Select Payment Type</option>
                <option value="downpayment">Down Payment</option>
                <option value="monthly">Monthly Payment</option>
                <option value="full">Full Payment</option>
                <option value="partial">Partial Payment</option>
                <option value="refund">Refund</option>
                <option value="reversal">Reversal</option>
                <option value="custom">Custom</option>
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Payment Method Field */}
          <Field
            label="Payment Method"
            required={true}
            inputComponent={
              <select
                className="form-select"
                value={payment.payment_method_id || ''}
                onChange={ev => setPayment({ ...payment, payment_method_id: ev.target.value })}
                disabled={isReadOnly}
                required
              >
                <option value="">Select Payment Method</option>
                {paymentMethods.map(method => (
                  <option key={method.id} value={method.id}>
                    {method.bank_name} - {method.account_name}
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Amount Paid Field */}
          <Field
            label="Amount Paid"
            required={true}
            inputComponent={
              <div className="input-group">
                <span className="input-group-text">₱</span>
                <input
                  className="form-control"
                  type="number"
                  step="0.01"
                  min="0"
                  value={payment.amount_paid || ''}
                  onChange={ev => setPayment({ ...payment, amount_paid: ev.target.value })}
                  required
                  placeholder="0.00"
                  disabled={isReadOnly}
                />
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Expected Amount Field */}
          <Field
            label="Expected Amount"
            inputComponent={
              <div className="input-group">
                <span className="input-group-text">₱</span>
                <input
                  className="form-control"
                  type="number"
                  step="0.01"
                  min="0"
                  value={payment.expected_amount || ''}
                  onChange={ev => setPayment({ ...payment, expected_amount: ev.target.value })}
                  placeholder="0.00"
                  disabled={isReadOnly}
                />
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Reference Number Field */}
          <Field
            label="Reference Number"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={payment.reference_number || ''}
                onChange={ev => setPayment({ ...payment, reference_number: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Payment reference number"
                required
                disabled={isReadOnly}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Receipt Image Field */}
          <Field
            label="Receipt Image"
            inputComponent={
              <div>
                {payment.receipt_image && (
                  <div className="mb-3">
                    <div className="d-flex flex-wrap gap-2 mb-2">
                      <div className="position-relative d-inline-block">
                        <img 
                          src={payment.receipt_image} 
                          alt="Receipt" 
                          style={{ maxWidth: '150px', maxHeight: '150px', objectFit: 'cover' }}
                          className="img-thumbnail"
                          onLoad={() => {
                            console.log('Receipt image loaded successfully:', payment.receipt_image);
                          }}
                          onError={(e) => {
                            console.error('Receipt image failed to load:', payment.receipt_image);
                            console.error('Error details:', e);
                            // Show a placeholder instead of hiding
                            e.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik03NSA0MEM4NS41IDQwIDk0IDQ4LjUgOTQgNTlDOTQgNjkuNSA4NS41IDc4IDc1IDc4QzY0LjUgNzggNTYgNjkuNSA1NiA1OUM1NiA0OC41IDY0LjUgNDAgNzUgNDBaIiBmaWxsPSIjOUNBM0FGIi8+CjxwYXRoIGQ9Ik03NSA4MEM4NS41IDgwIDk0IDg4LjUgOTQgOTlDOTQgMTA5LjUgODUuNSAxMTggNzUgMTE4QzY0LjUgMTE4IDU2IDEwOS41IDU2IDk5QzU2IDg4LjUgNjQuNSA4MCA3NSA4MFoiIGZpbGw9IiM5Q0EzQUYiLz4KPC9zdmc+';
                            e.target.alt = 'Receipt image failed to load';
                          }}
                        />
                        <button 
                          type="button" 
                          className="btn btn-sm btn-danger position-absolute top-0 end-0"
                          style={{ transform: 'translate(50%, -50%)' }}
                          onClick={handleRemoveReceiptImage}
                          title="Remove receipt image"
                        >
                          <FontAwesomeIcon icon={solidIconMap.times} />
                        </button>
                      </div>
                    </div>
                  </div>
                )}
                <div className="mb-3">
                  <input
                    type="file"
                    className="form-control"
                    multiple
                    accept="image/*"
                    onChange={handleReceiptFileSelect}
                    id="receipt-images"
                    disabled={isReadOnly}
                  />
                  <label htmlFor="receipt-images" className="form-label">
                    <small className="text-dark">
                      Select receipt image(s) (JPG, PNG, GIF, WebP). Each image should be less than 2MB.
                      {selectedReceiptFiles.length > 0 && (
                        <span className="text-info d-block mt-1">
                          {selectedReceiptFiles.length} new file(s) selected. These will replace existing receipt image.
                        </span>
                      )}
                    </small>
                  </label>
                </div>
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Status Field */}
          <Field
            label="Status"
            inputComponent={
              <select
                className="form-select"
                value={payment.status || 'pending'}
                onChange={ev => setPayment({ ...payment, status: ev.target.value })}
                disabled={isReadOnly}
              >
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="confirmed">Confirmed</option>
                <option value="rejected">Rejected</option>
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Rejection Reason Field */}
          {payment.status === 'rejected' && (
            <Field
              label="Rejection Reason"
              inputComponent={
                <textarea
                  className="form-control"
                  rows="3"
                  value={payment.rejection_reason || ''}
                  onChange={ev => setPayment({ ...payment, rejection_reason: DOMPurify.sanitize(ev.target.value) })}
                  placeholder="Reason for rejection"
                  disabled={isReadOnly}
                />
              }
              labelClass="col-sm-12 col-md-3"
              inputClass="col-sm-12 col-md-9"
            />
          )}

          {/* Payment Date Field */}
          <Field
            label="Payment Date"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={payment.payment_date || ''}
                onChange={ev => setPayment({ ...payment, payment_date: ev.target.value })}
                required
                disabled={isReadOnly}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Notes Field */}
          <Field
            label="Notes"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={payment.notes || ''}
                onChange={ev => setPayment({ ...payment, notes: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Additional notes or comments"
                disabled={isReadOnly}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
        </div>
        <div className="card-footer d-flex justify-content-between">
          <div>
            <Link type="button" to="/payment-management/payments" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
              Cancel
            </Link> &nbsp;
            <button type="submit" className="btn btn-secondary" disabled={isReadOnly}>
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          <div>
            {payment.id && payment.status === 'pending' && !isReadOnly && (
              <>
                <button 
                  type="button" 
                  className="btn btn-success me-2" 
                  onClick={handleApprove}
                  disabled={isLoading}
                >
                  <FontAwesomeIcon icon={solidIconMap.check} className="me-2" />
                  Approve
                </button>
                <button 
                  type="button" 
                  className="btn btn-danger me-2" 
                  onClick={handleReject}
                  disabled={isLoading}
                >
                  <FontAwesomeIcon icon={solidIconMap.times} className="me-2" />
                  Reject
                </button>
              </>
            )}
            {payment.id && payment.status === 'approved' && !isReadOnly && (
              <button 
                type="button" 
                className="btn btn-primary me-2" 
                onClick={handleConfirm}
                disabled={isLoading}
              >
                <FontAwesomeIcon icon={solidIconMap.check} className="me-2" />
                Confirm
              </button>
            )}
            {payment.id && selectedInvoice && (
              <button 
                type="button" 
                className="btn btn-info me-2" 
                onClick={handleSendUpdateInvoice}
                disabled={isLoading}
              >
                <FontAwesomeIcon icon={solidIconMap.envelope} className="me-2" />
                Send Update Invoice
              </button>
            )}
            {payment.id && !isReadOnly && (
              <button 
                type="button" 
                className="btn btn-danger" 
                onClick={handleDelete}
                disabled={isLoading}
              >
                <FontAwesomeIcon icon={solidIconMap.trash} className="me-2" />
                Delete
              </button>
            )}
          </div>
        </div>
      </form>
    </div>
    <ToastMessage ref={toastAction} />
    </>
  );
}
