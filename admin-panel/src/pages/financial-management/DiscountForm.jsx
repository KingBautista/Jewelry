import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function DiscountForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Discount');
  const [discount, setDiscount] = useState({
    id: null,
    name: '',
    code: '',
    amount: '',
    type: 'fixed',
    description: '',
    valid_from: '',
    valid_until: '',
    usage_limit: '',
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);

  // Load discount data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/financial-management/discounts/${id}`)
        .then(({ data }) => {
          setDiscount({
            ...data,
            valid_from: data.valid_from ? data.valid_from.split('T')[0] : '',
            valid_until: data.valid_until ? data.valid_until.split('T')[0] : '',
            usage_limit: data.usage_limit || '',
          });
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
    if (!discount.name || !discount.code || !discount.amount) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    // Validate amount
    const amount = parseFloat(discount.amount);
    if (isNaN(amount) || amount < 0) {
      toastAction.current.showToast('Amount must be a positive number', 'warning');
      return;
    }

    // Validate percentage amount
    if (discount.type === 'percentage' && amount > 100) {
      toastAction.current.showToast('Percentage amount cannot exceed 100%', 'warning');
      return;
    }

    // Validate date range
    if (discount.valid_from && discount.valid_until) {
      const fromDate = new Date(discount.valid_from);
      const untilDate = new Date(discount.valid_until);
      if (fromDate >= untilDate) {
        toastAction.current.showToast('Valid until date must be after valid from date', 'warning');
        return;
      }
    }

    // Validate usage limit
    if (discount.usage_limit && (isNaN(discount.usage_limit) || discount.usage_limit < 1)) {
      toastAction.current.showToast('Usage limit must be a positive number', 'warning');
      return;
    }

    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...discount,
      amount: amount,
      active: isActive,
      valid_from: discount.valid_from || null,
      valid_until: discount.valid_until || null,
      usage_limit: discount.usage_limit ? parseInt(discount.usage_limit) : null,
    };

    const request = discount.id
      ? axiosClient.put(`/financial-management/discounts/${discount.id}`, submitData)
      : axiosClient.post('/financial-management/discounts', submitData);

    request
      .then(() => {
        const action = discount.id ? 'updated' : 'added';
        toastAction.current.showToast(`Discount has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/financial-management/discounts'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!discount.id) return;
    
    if (window.confirm('Are you sure you want to delete this discount?')) {
      setIsLoading(true);
      axiosClient.delete(`/financial-management/discounts/${discount.id}`)
        .then(() => {
          toastAction.current.showToast('Discount has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/financial-management/discounts'), 2000);
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
            {discount.id ? 'Edit Discount' : 'Create New Discount'}
          </h4>
          {!discount.id && <p className="tip-message">Create a new discount configuration for your jewelry business.</p>}
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
                value={discount.name}
                onChange={ev => setDiscount({ ...discount, name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., First-time Buyer, Bulk Purchase"
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
                value={discount.code}
                onChange={ev => setDiscount({ ...discount, code: DOMPurify.sanitize(ev.target.value.toUpperCase()) })}
                required
                placeholder="e.g., FIRST_TIME, BULK"
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
                value={discount.type}
                onChange={ev => setDiscount({ ...discount, type: ev.target.value })}
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
            label={`Amount ${discount.type === 'percentage' ? '(%)' : '(₱)'}`}
            required={true}
            inputComponent={
              <div className="input-group">
                <input
                  className="form-control"
                  type="number"
                  step="0.01"
                  min="0"
                  max={discount.type === 'percentage' ? '100' : undefined}
                  value={discount.amount}
                  onChange={ev => setDiscount({ ...discount, amount: ev.target.value })}
                  required
                  placeholder={discount.type === 'percentage' ? 'e.g., 10.00' : 'e.g., 1000.00'}
                />
                <span className="input-group-text">
                  {discount.type === 'percentage' ? '%' : '₱'}
                </span>
              </div>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Valid From Field */}
          <Field
            label="Valid From"
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={discount.valid_from}
                onChange={ev => setDiscount({ ...discount, valid_from: ev.target.value })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Valid Until Field */}
          <Field
            label="Valid Until"
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={discount.valid_until}
                onChange={ev => setDiscount({ ...discount, valid_until: ev.target.value })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Usage Limit Field */}
          <Field
            label="Usage Limit"
            inputComponent={
              <input
                className="form-control"
                type="number"
                min="1"
                value={discount.usage_limit}
                onChange={ev => setDiscount({ ...discount, usage_limit: ev.target.value })}
                placeholder="Leave empty for unlimited usage"
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
                value={discount.description}
                onChange={ev => setDiscount({ ...discount, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Optional description for this discount"
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
              <Link type="button" to="/financial-management/discounts" className="btn btn-secondary" style={{ minWidth: '120px' }}>
                <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
                Cancel
              </Link>
              <button type="submit" className="btn btn-secondary" style={{ minWidth: '120px' }}>
                <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
                {buttonText} &nbsp;
                {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
              </button>
            </div>
            {discount.id && (
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
