import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import Dropzone from "../../components/Dropzone";
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
    product_image: '',
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
  const [uploadedImages, setUploadedImages] = useState([]);
  
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
    
    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...invoice,
      price: parseFloat(invoice.price) || 0,
      active: isActive,
    };

    const request = invoice.id
      ? axiosClient.put(`/invoice-management/invoices/${invoice.id}`, submitData)
      : axiosClient.post('/invoice-management/invoices', submitData);

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

  // Handle image upload
  const handleImageUpload = (uploadedFiles) => {
    if (uploadedFiles && uploadedFiles.length > 0) {
      const imageUrl = uploadedFiles[0].url || uploadedFiles[0].path;
      setInvoice({ ...invoice, product_image: imageUrl });
      setUploadedImages(uploadedFiles);
    }
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
                    {customer.full_name || customer.name} ({customer.email})
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

          {/* Product Image Field */}
          <Field
            label="Product Image"
            inputComponent={
              <div>
                {invoice.product_image && (
                  <div className="mb-3">
                    <img 
                      src={invoice.product_image} 
                      alt="Product" 
                      style={{ maxWidth: '200px', maxHeight: '200px', objectFit: 'cover' }}
                      className="img-thumbnail"
                    />
                    <div className="mt-2">
                      <button 
                        type="button" 
                        className="btn btn-sm btn-danger"
                        onClick={() => setInvoice({ ...invoice, product_image: '' })}
                      >
                        <FontAwesomeIcon icon={solidIconMap.trash} className="me-1" />
                        Remove Image
                      </button>
                    </div>
                  </div>
                )}
                <Dropzone
                  options={{
                    accept: {
                      image: {
                        type: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                        maxSize: 2 * 1024 * 1024 // 2MB
                      }
                    },
                    postUrl: '/content-management/media-library/upload',
                    redirectUrl: '#'
                  }}
                  onChange={handleImageUpload}
                />
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
