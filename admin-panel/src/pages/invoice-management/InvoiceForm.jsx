import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function InvoiceForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Invoice');
  const [invoice, setInvoice] = useState({
    id: null,
    invoice_number: '',
    customer_id: '',
    product_name: '',
    description: '',
    price: '',
    product_images: [],
    payment_term_id: '',
    tax_id: '',
    fee_id: '',
    discount_id: '',
    shipping_address: '',
    issue_date: '',
    due_date: '',
    status: 'draft',
    notes: '',
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [selectedFiles, setSelectedFiles] = useState([]);
  
  // Dropdown data
  const [customers, setCustomers] = useState([]);
  const [paymentTerms, setPaymentTerms] = useState([]);
  const [taxes, setTaxes] = useState([]);
  const [fees, setFees] = useState([]);
  const [discounts, setDiscounts] = useState([]);

  // Load dropdown data
  useEffect(() => {
    const loadDropdownData = async () => {
      try {
        const [customersRes, paymentTermsRes, taxesRes, feesRes, discountsRes] = await Promise.all([
          axiosClient.get('/options/customers'),
          axiosClient.get('/options/payment-terms'),
          axiosClient.get('/options/taxes'),
          axiosClient.get('/options/fees'),
          axiosClient.get('/options/discounts')
        ]);

        setCustomers(customersRes.data || []);
        setPaymentTerms(paymentTermsRes.data || []);
        setTaxes(taxesRes.data || []);
        setFees(feesRes.data || []);
        setDiscounts(discountsRes.data || []);
      } catch (error) {
        console.error('Error loading dropdown data:', error);
      }
    };

    loadDropdownData();
  }, []);

  // Load invoice data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/invoice-management/invoices/${id}`)
        .then(({ data }) => {
          const invoiceData = data.data || data;
          
          // Ensure dates are in the correct format for HTML date inputs
          if (invoiceData.issue_date) {
            // If the date is already in YYYY-MM-DD format, use it as is
            // If it's in a different format, convert it
            const issueDate = new Date(invoiceData.issue_date);
            if (!isNaN(issueDate.getTime())) {
              invoiceData.issue_date = issueDate.toISOString().split('T')[0];
            }
          }
          
          if (invoiceData.due_date) {
            const dueDate = new Date(invoiceData.due_date);
            if (!isNaN(dueDate.getTime())) {
              invoiceData.due_date = dueDate.toISOString().split('T')[0];
            }
          }
          
          setInvoice(invoiceData);
          setIsLoading(false);
          setIsActive(invoiceData.active);
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
    
    // Validate required fields
    if (!invoice.customer_id || !invoice.product_name || !invoice.price) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    // Validate amount
    const amount = parseFloat(invoice.price);
    if (isNaN(amount) || amount < 0) {
      toastAction.current.showToast('Price must be a positive number', 'warning');
      return;
    }

    setIsLoading(true);

    // Create FormData for file uploads
    const formData = new FormData();
    
    // Add all invoice data with proper formatting - only append if value exists
    if (invoice.invoice_number) formData.append('invoice_number', invoice.invoice_number);
    if (invoice.customer_id) formData.append('customer_id', invoice.customer_id);
    if (invoice.product_name) formData.append('product_name', invoice.product_name);
    if (invoice.description) formData.append('description', invoice.description);
    if (invoice.price) formData.append('price', invoice.price);
    if (invoice.payment_term_id) formData.append('payment_term_id', invoice.payment_term_id);
    if (invoice.tax_id) formData.append('tax_id', invoice.tax_id);
    if (invoice.fee_id) formData.append('fee_id', invoice.fee_id);
    if (invoice.discount_id) formData.append('discount_id', invoice.discount_id);
    if (invoice.shipping_address) formData.append('shipping_address', invoice.shipping_address);
    if (invoice.issue_date) formData.append('issue_date', invoice.issue_date);
    if (invoice.due_date) formData.append('due_date', invoice.due_date);
    if (invoice.status) formData.append('status', invoice.status);
    if (invoice.notes) formData.append('notes', invoice.notes);
    formData.append('active', isActive);
    
    // Add invoice ID for updates
    if (invoice.id) {
      formData.append('invoice_id', invoice.id);
    }
    
    // Add selected files
    selectedFiles.forEach((file, index) => {
      formData.append(`product_images[${index}]`, file);
    });

    // Let axios handle the Content-Type header automatically
    const config = {};

    // Use POST for both create and update to avoid FormData parsing issues with PUT
    const request = axiosClient.post('/invoice-management/invoices', formData, config);

    request
      .then(() => {
        const action = invoice.id ? 'updated' : 'added';
        toastAction.current.showToast(`Invoice has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/invoice-management/invoices'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!invoice.id) return;
    
    if (window.confirm('Are you sure you want to delete this invoice?')) {
      setIsLoading(true);
      axiosClient.delete(`/invoice-management/invoices/${invoice.id}`)
        .then(() => {
          toastAction.current.showToast('Invoice has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/invoice-management/invoices'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle cancel invoice
  const handleCancel = () => {
    if (!invoice.id) return;
    
    if (window.confirm('Are you sure you want to cancel this invoice?')) {
      setIsLoading(true);
      axiosClient.patch(`/invoice-management/invoices/${invoice.id}/cancel`)
        .then(() => {
          toastAction.current.showToast('Invoice has been cancelled.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/invoice-management/invoices'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle download PDF
  const handleDownloadPdf = async () => {
    if (!invoice.id) return;
    
    try {
      setIsLoading(true);
      toastAction.current.showToast('Generating PDF...', 'info');
      
      // Get the token for authentication
      const token = localStorage.getItem('ACCESS_TOKEN');
      
      // Make the request with proper headers
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/invoice-management/invoices/${invoice.id}/pdf`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf',
        },
      });
      
      if (!response.ok) {
        throw new Error('Failed to generate PDF');
      }
      
      // Create blob from response
      const blob = await response.blob();
      
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `invoice-${invoice.invoice_number || invoice.id}.pdf`;
      document.body.appendChild(link);
      link.click();
      
      // Cleanup
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      toastAction.current.showToast('PDF downloaded successfully!', 'success');
      setIsLoading(false);
    } catch (error) {
      console.error('PDF download failed:', error);
      toastAction.current.showToast('Failed to download PDF. Please try again.', 'error');
      setIsLoading(false);
    }
  };

  // Handle send email
  const handleSendEmail = () => {
    if (!invoice.id) return;
    
    if (window.confirm('Are you sure you want to send this invoice via email?\n\nThe invoice PDF will be attached to the email.')) {
      setIsLoading(true);
      toastAction.current.showToast('Sending email...', 'info');
      
      axiosClient.post(`/invoice-management/invoices/${invoice.id}/send-email`)
        .then((response) => {
          toastAction.current.showToast('Invoice has been sent via email successfully!', 'success');
          setIsLoading(false);
          console.log('Email sent successfully:', response.data);
        })
        .catch((errors) => {
          console.error('Email sending failed:', errors);
          setIsLoading(false);
          if (errors.response?.data?.message) {
            toastAction.current.showToast(`Email failed: ${errors.response.data.message}`, 'error');
          } else {
            toastAction.current.showToast('Failed to send email. Please try again.', 'error');
          }
        });
    }
  };

  // Handle file selection
  const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    setSelectedFiles(files);
    
    // Create preview URLs for display
    const previewUrls = files.map(file => URL.createObjectURL(file));
    setInvoice({ 
      ...invoice, 
      product_images: previewUrls // Replace with new preview URLs, don't append to existing
    });
  };

  // Handle remove individual image
  const handleRemoveImage = (indexToRemove) => {
    const updatedImages = invoice.product_images.filter((_, index) => index !== indexToRemove);
    const updatedFiles = selectedFiles.filter((_, index) => index !== indexToRemove);
    setInvoice({ ...invoice, product_images: updatedImages });
    setSelectedFiles(updatedFiles);
  };

  // Handle remove all images
  const handleRemoveAllImages = () => {
    setInvoice({ ...invoice, product_images: [] });
    setSelectedFiles([]);
  };

  return (
    <>
    <div className="card">
      <form onSubmit={onSubmit}>
        <div className="card-header">
          <h4>
            {invoice.id ? 'Edit Invoice' : 'Create New Invoice'}
          </h4>
          {!invoice.id && <p className="tip-message">Create a new invoice for your jewelry business.</p>}
        </div>
        <div className="card-body">
          {/* Invoice Number Field */}
          <Field
            label="Invoice Number"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={invoice.invoice_number || ''}
                onChange={ev => setInvoice({ ...invoice, invoice_number: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Auto-generated if left empty"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Customer Field */}
          <Field
            label="Customer"
            required={true}
            inputComponent={
              <select
                className="form-select"
                value={invoice.customer_id || ''}
                onChange={ev => setInvoice({ ...invoice, customer_id: ev.target.value })}
                required
              >
                <option value="">Select Customer</option>
                {customers.map(customer => (
                  <option key={customer.id} value={customer.id}>
                    {`${customer.first_name} ${customer.last_name}`} ({customer.email})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Product Name Field */}
          <Field
            label="Product Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={invoice.product_name || ''}
                onChange={ev => setInvoice({ ...invoice, product_name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., Diamond Ring, Gold Necklace"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Description Field */}
          <Field
            label="Description"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={invoice.description || ''}
                onChange={ev => setInvoice({ ...invoice, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Product description and details"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Price Field */}
          <Field
            label="Price"
            required={true}
            inputComponent={
              <div className="input-group">
                <span className="input-group-text">₱</span>
                <input
                  className="form-control"
                  type="number"
                  step="0.01"
                  min="0"
                  value={invoice.price || ''}
                  onChange={ev => setInvoice({ ...invoice, price: ev.target.value })}
                  required
                  placeholder="0.00"
                />
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Product Images Field */}
          <Field
            label="Product Images"
            inputComponent={
              <div>
                {invoice.product_images && invoice.product_images.length > 0 && (
                  <div className="mb-3">
                    <div className="d-flex flex-wrap gap-2 mb-2">
                      {invoice.product_images.map((imageUrl, index) => (
                        <div key={index} className="position-relative d-inline-block">
                          <img 
                            src={imageUrl} 
                            alt={`Product ${index + 1}`} 
                            style={{ maxWidth: '150px', maxHeight: '150px', objectFit: 'cover' }}
                            className="img-thumbnail"
                            onLoad={() => {
                              console.log('Image loaded successfully:', imageUrl);
                            }}
                            onError={(e) => {
                              console.error('Image failed to load:', imageUrl);
                              console.error('Error details:', e);
                              // Show a placeholder instead of hiding
                              e.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik03NSA0MEM4NS41IDQwIDk0IDQ4LjUgOTQgNTlDOTQgNjkuNSA4NS41IDc4IDc1IDc4QzY0LjUgNzggNTYgNjkuNSA1NiA1OUM1NiA0OC41IDY0LjUgNDAgNzUgNDBaIiBmaWxsPSIjOUNBM0FGIi8+CjxwYXRoIGQ9Ik03NSA4MEM4NS41IDgwIDk0IDg4LjUgOTQgOTlDOTQgMTA5LjUgODUuNSAxMTggNzUgMTE4QzY0LjUgMTE4IDU2IDEwOS41IDU2IDk5QzU2IDg4LjUgNjQuNSA4MCA3NSA4MFoiIGZpbGw9IiM5Q0EzQUYiLz4KPC9zdmc+';
                              e.target.alt = 'Image failed to load';
                            }}
                          />
                          <button 
                            type="button" 
                            className="btn btn-sm btn-danger position-absolute top-0 end-0"
                            style={{ transform: 'translate(50%, -50%)' }}
                            onClick={() => handleRemoveImage(index)}
                            title="Remove this image"
                          >
                            <FontAwesomeIcon icon={solidIconMap.times} />
                          </button>
                        </div>
                      ))}
                    </div>
                    <button 
                      type="button" 
                      className="btn btn-sm btn-outline-danger"
                      onClick={handleRemoveAllImages}
                    >
                      <FontAwesomeIcon icon={solidIconMap.trash} className="me-1" />
                      Remove All Images
                    </button>
                  </div>
                )}
                <div className="mb-3">
                  <input
                    type="file"
                    className="form-control"
                    multiple
                    accept="image/*"
                    onChange={handleFileSelect}
                    id="product-images"
                  />
                  <label htmlFor="product-images" className="form-label">
                    <small className="text-muted">
                      Select multiple images (JPG, PNG, GIF, WebP). Each image should be less than 2MB.
                      {selectedFiles.length > 0 && (
                        <span className="text-info d-block mt-1">
                          {selectedFiles.length} new file(s) selected. These will replace existing images.
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

          {/* Payment Term Field */}
          <Field
            label="Payment Term"
            inputComponent={
              <select
                className="form-select"
                value={invoice.payment_term_id || ''}
                onChange={ev => setInvoice({ ...invoice, payment_term_id: ev.target.value })}
              >
                <option value="">Select Payment Term</option>
                {paymentTerms.map(term => (
                  <option key={term.id} value={term.id}>
                    {term.name} ({term.code || term.id})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Tax Field */}
          <Field
            label="Tax"
            inputComponent={
              <select
                className="form-select"
                value={invoice.tax_id || ''}
                onChange={ev => setInvoice({ ...invoice, tax_id: ev.target.value })}
              >
                <option value="">Select Tax</option>
                {taxes.map(tax => (
                  <option key={tax.id} value={tax.id}>
                    {tax.name} ({tax.percentage || tax.rate}%)
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Fee Field */}
          <Field
            label="Fee"
            inputComponent={
              <select
                className="form-select"
                value={invoice.fee_id || ''}
                onChange={ev => setInvoice({ ...invoice, fee_id: ev.target.value })}
              >
                <option value="">Select Fee</option>
                {fees.map(fee => (
                  <option key={fee.id} value={fee.id}>
                    {fee.name} (₱{fee.amount || fee.fee_amount})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Discount Field */}
          <Field
            label="Discount"
            inputComponent={
              <select
                className="form-select"
                value={invoice.discount_id || ''}
                onChange={ev => setInvoice({ ...invoice, discount_id: ev.target.value })}
              >
                <option value="">Select Discount</option>
                {discounts.map(discount => (
                  <option key={discount.id} value={discount.id}>
                    {discount.name} ({discount.percentage || discount.discount_percentage}%)
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Shipping Address Field */}
          <Field
            label="Shipping Address"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={invoice.shipping_address || ''}
                onChange={ev => setInvoice({ ...invoice, shipping_address: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Customer shipping address"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Issue Date Field */}
          <Field
            label="Issue Date"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={invoice.issue_date || ''}
                onChange={ev => setInvoice({ ...invoice, issue_date: ev.target.value })}
                required
                placeholder="YYYY-MM-DD"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Due Date Field */}
          <Field
            label="Due Date"
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={invoice.due_date || ''}
                onChange={ev => setInvoice({ ...invoice, due_date: ev.target.value })}
                placeholder="YYYY-MM-DD"
              />
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
                value={invoice.status || 'draft'}
                onChange={ev => setInvoice({ ...invoice, status: ev.target.value })}
              >
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="cancelled">Cancelled</option>
              </select>
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
                value={invoice.notes || ''}
                onChange={ev => setInvoice({ ...invoice, notes: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Additional notes or comments"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Active Field */}
          <Field
            label="Active"
            inputComponent={
              <input
                className="form-check-input"
                type="checkbox"
                checked={isActive}
                onChange={() => setIsActive(!isActive)}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
        </div>
        <div className="card-footer d-flex justify-content-between">
          <div>
            <Link type="button" to="/invoice-management/invoices" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
              Cancel
            </Link> &nbsp;
            <button type="submit" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          <div>
            {invoice.id && (
              <>
                <button 
                  type="button" 
                  className="btn btn-primary me-2" 
                  onClick={handleDownloadPdf}
                  disabled={isLoading}
                >
                  <FontAwesomeIcon icon={solidIconMap.file} className="me-2" />
                  Download PDF
                </button>
                <button 
                  type="button" 
                  className="btn btn-success me-2" 
                  onClick={handleSendEmail}
                  disabled={isLoading}
                >
                  <FontAwesomeIcon icon={solidIconMap.envelope} className="me-2" />
                  Send Email
                </button>
              </>
            )}
            {invoice.id && invoice.status !== 'cancelled' && (
              <button 
                type="button" 
                className="btn btn-warning me-2" 
                onClick={handleCancel}
                disabled={isLoading}
              >
                <FontAwesomeIcon icon={solidIconMap.times} className="me-2" />
                Cancel Invoice
              </button>
            )}
            {invoice.id && (
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
