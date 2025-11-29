import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function FeeForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Fee');
  const [fee, setFee] = useState({
    id: null,
    name: '',
    code: '',
    amount: '',
    type: 'fixed',
    description: '',
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);

  // Load fee data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/financial-management/fees/${id}`)
        .then(({ data }) => {
          setFee(data);
          setIsLoading(false);
          setIsActive(data.active);
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
    if (!fee.name || !fee.code || !fee.amount) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    // Validate amount
    const amount = parseFloat(fee.amount);
    if (isNaN(amount) || amount < 0) {
      toastAction.current.showToast('Amount must be a positive number', 'warning');
      return;
    }

    // Validate percentage amount
    if (fee.type === 'percentage' && amount > 100) {
      toastAction.current.showToast('Percentage amount cannot exceed 100%', 'warning');
      return;
    }

    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...fee,
      amount: amount,
      active: isActive,
    };

    const request = fee.id
      ? axiosClient.put(`/financial-management/fees/${fee.id}`, submitData)
      : axiosClient.post('/financial-management/fees', submitData);

    request
      .then(() => {
        const action = fee.id ? 'updated' : 'added';
        toastAction.current.showToast(`Fee has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/financial-management/fees'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!fee.id) return;
    
    if (window.confirm('Are you sure you want to delete this fee?')) {
      setIsLoading(true);
      axiosClient.delete(`/financial-management/fees/${fee.id}`)
        .then(() => {
          toastAction.current.showToast('Fee has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/financial-management/fees'), 2000);
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
            {fee.id ? 'Edit Fee' : 'Create New Fee'}
          </h4>
          {!fee.id && <p className="tip-message">Create a new fee configuration for your jewelry business.</p>}
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
                value={fee.name}
                onChange={ev => setFee({ ...fee, name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., Delivery Fee, Processing Fee"
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
                value={fee.code}
                onChange={ev => setFee({ ...fee, code: DOMPurify.sanitize(ev.target.value.toUpperCase()) })}
                required
                placeholder="e.g., DELIVERY, PROCESSING"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Type Field */}
          <Field
            label="Type"
            required={true}
            inputComponent={
              <select
                className="form-select"
                value={fee.type}
                onChange={ev => setFee({ ...fee, type: ev.target.value })}
                required
              >
                <option value="fixed">Fixed Amount</option>
                <option value="percentage">Percentage</option>
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Amount Field */}
          <Field
            label={`Amount ${fee.type === 'percentage' ? '(%)' : '(₱)'}`}
            required={true}
            inputComponent={
              <div className="input-group">
                <input
                  className="form-control"
                  type="number"
                  step="0.01"
                  min="0"
                  max={fee.type === 'percentage' ? '100' : undefined}
                  value={fee.amount}
                  onChange={ev => setFee({ ...fee, amount: ev.target.value })}
                  required
                  placeholder={fee.type === 'percentage' ? 'e.g., 5.00' : 'e.g., 500.00'}
                />
                <span className="input-group-text">
                  {fee.type === 'percentage' ? '%' : '₱'}
                </span>
              </div>
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
                value={fee.description}
                onChange={ev => setFee({ ...fee, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Optional description for this fee"
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
              <Link type="button" to="/financial-management/fees" className="btn btn-secondary" style={{ minWidth: '120px' }}>
                <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
                Cancel
              </Link>
              <button type="submit" className="btn btn-secondary" style={{ minWidth: '120px' }}>
                <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
                {buttonText} &nbsp;
                {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
              </button>
            </div>
            {fee.id && (
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
