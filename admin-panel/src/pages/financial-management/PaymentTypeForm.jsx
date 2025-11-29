import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function PaymentTypeForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Payment Type');
  const [paymentType, setPaymentType] = useState({
    id: null,
    name: '',
    code: '',
    description: '',
    is_active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);

  // Load payment type data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/financial-management/payment-types/${id}`)
        .then(({ data }) => {
          setPaymentType(data);
          setIsLoading(false);
          setIsActive(data.is_active);
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
    if (!paymentType.name || !paymentType.code) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...paymentType,
      is_active: isActive,
    };

    const request = paymentType.id
      ? axiosClient.put(`/financial-management/payment-types/${paymentType.id}`, submitData)
      : axiosClient.post('/financial-management/payment-types', submitData);

    request
      .then(() => {
        const action = paymentType.id ? 'updated' : 'added';
        toastAction.current.showToast(`Payment type has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/financial-management/payment-types'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!paymentType.id) return;
    
    if (window.confirm('Are you sure you want to delete this payment type?')) {
      setIsLoading(true);
      axiosClient.delete(`/financial-management/payment-types/${paymentType.id}`)
        .then(() => {
          toastAction.current.showToast('Payment type has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/financial-management/payment-types'), 2000);
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
            {paymentType.id ? 'Edit Payment Type' : 'Create New Payment Type'}
          </h4>
          {!paymentType.id && <p className="tip-message">Create a new payment type configuration for your jewelry business.</p>}
        </div>
        <div className="card-body">
          {/* Name Field */}
          <Field
            label="Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentType.name}
                onChange={ev => setPaymentType({ ...paymentType, name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., Credit Card, Cash Payment"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Code Field */}
          <Field
            label="Code"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentType.code}
                onChange={ev => setPaymentType({ ...paymentType, code: DOMPurify.sanitize(ev.target.value.toUpperCase()) })}
                required
                placeholder="e.g., CREDIT_CARD, CASH"
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
                value={paymentType.description}
                onChange={ev => setPaymentType({ ...paymentType, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Optional description for this payment type"
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
        <div className="card-footer">
          <div className="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            <div className="d-flex flex-column flex-sm-row gap-2">
              <Link type="button" to="/financial-management/payment-types" className="btn btn-secondary" style={{ minWidth: '120px' }}>
                <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
                Cancel
              </Link>
              <button type="submit" className="btn btn-secondary" style={{ minWidth: '120px' }}>
                <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
                {buttonText} &nbsp;
                {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
              </button>
            </div>
            {paymentType.id && (
              <button 
                type="button" 
                className="btn btn-danger" 
                onClick={handleDelete}
                disabled={isLoading}
                style={{ minWidth: '120px' }}
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
