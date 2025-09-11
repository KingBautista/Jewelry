import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

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
  
  // Dropdown data
  const [invoices, setInvoices] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [paymentMethods, setPaymentMethods] = useState([]);

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
          setPayment(paymentData);
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

    // Prepare the data for submission
    const submitData = {
      ...payment,
      amount_paid: parseFloat(payment.amount_paid) || 0,
      expected_amount: parseFloat(payment.expected_amount) || 0,
    };

    const request = payment.id
      ? axiosClient.put(`/payment-management/payments/${payment.id}`, submitData)
      : axiosClient.post('/payment-management/payments', submitData);

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
      axiosClient.patch(`/payment-management/payments/${payment.id}/confirm`)
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

  return (
    <>
    <div className="card">
      <form onSubmit={onSubmit}>
        <div className="card-header">
          <h4>
            {payment.id ? 'Edit Payment' : 'Create New Payment'}
          </h4>
          {!payment.id && <p className="tip-message">Create a new payment record for your jewelry business.</p>}
        </div>
        <div className="card-body">
          {/* Invoice Field */}
          <Field
            label="Invoice"
            required={true}
            inputComponent={
              <select
                className="form-select"
                value={payment.invoice_id || ''}
                onChange={ev => setPayment({ ...payment, invoice_id: ev.target.value })}
                required
              >
                <option value="">Select Invoice</option>
                {invoices.map(invoice => (
                  <option key={invoice.id} value={invoice.id}>
                    {invoice.invoice_number} - {invoice.product_name} ({invoice.formatted_total_amount})
                  </option>
                ))}
              </select>
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
                value={payment.customer_id || ''}
                onChange={ev => setPayment({ ...payment, customer_id: ev.target.value })}
                required
              >
                <option value="">Select Customer</option>
                {customers.map(customer => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name} ({customer.email})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

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
            inputComponent={
              <select
                className="form-select"
                value={payment.payment_method_id || ''}
                onChange={ev => setPayment({ ...payment, payment_method_id: ev.target.value })}
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
                />
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Reference Number Field */}
          <Field
            label="Reference Number"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={payment.reference_number || ''}
                onChange={ev => setPayment({ ...payment, reference_number: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Payment reference number"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Receipt Image Field */}
          <Field
            label="Receipt Image"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={payment.receipt_image || ''}
                onChange={ev => setPayment({ ...payment, receipt_image: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Receipt image URL"
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
                value={payment.status || 'pending'}
                onChange={ev => setPayment({ ...payment, status: ev.target.value })}
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
            <button type="submit" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          <div>
            {payment.id && payment.status === 'pending' && (
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
            {payment.id && payment.status === 'approved' && (
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
            {payment.id && (
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
