import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function TaxForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Tax');
  const [tax, setTax] = useState({
    id: null,
    name: '',
    code: '',
    rate: '',
    description: '',
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);

  // Load tax data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/financial-management/taxes/${id}`)
        .then(({ data }) => {
          setTax(data);
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
    if (!tax.name || !tax.code || !tax.rate) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    // Validate rate
    const rate = parseFloat(tax.rate);
    if (isNaN(rate) || rate < 0 || rate > 100) {
      toastAction.current.showToast('Rate must be a number between 0 and 100', 'warning');
      return;
    }

    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...tax,
      rate: rate,
      active: isActive,
    };

    const request = tax.id
      ? axiosClient.put(`/financial-management/taxes/${tax.id}`, submitData)
      : axiosClient.post('/financial-management/taxes', submitData);

    request
      .then(() => {
        const action = tax.id ? 'updated' : 'added';
        toastAction.current.showToast(`Tax has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/financial-management/taxes'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!tax.id) return;
    
    if (window.confirm('Are you sure you want to delete this tax?')) {
      setIsLoading(true);
      axiosClient.delete(`/financial-management/taxes/${tax.id}`)
        .then(() => {
          toastAction.current.showToast('Tax has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/financial-management/taxes'), 2000);
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
            {tax.id ? 'Edit Tax' : 'Create New Tax'}
          </h4>
          {!tax.id && <p className="tip-message">Create a new tax configuration for your jewelry business.</p>}
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
                value={tax.name}
                onChange={ev => setTax({ ...tax, name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., VAT, Sales Tax"
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
                value={tax.code}
                onChange={ev => setTax({ ...tax, code: DOMPurify.sanitize(ev.target.value.toUpperCase()) })}
                required
                placeholder="e.g., VAT, SALES_TAX"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Rate Field */}
          <Field
            label="Rate (%)"
            required={true}
            inputComponent={
              <div className="input-group">
                <input
                  className="form-control"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value={tax.rate}
                  onChange={ev => setTax({ ...tax, rate: ev.target.value })}
                  required
                  placeholder="e.g., 12.00"
                />
                <span className="input-group-text">%</span>
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
                value={tax.description}
                onChange={ev => setTax({ ...tax, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Optional description for this tax"
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
            <Link type="button" to="/financial-management/taxes" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
              Cancel
            </Link> &nbsp;
            <button type="submit" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          {tax.id && (
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
      </form>
    </div>
    <ToastMessage ref={toastAction} />
    </>
  );
}
