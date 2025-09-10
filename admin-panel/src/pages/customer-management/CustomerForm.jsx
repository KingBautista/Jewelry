import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import PasswordGenerator from "../../components/PasswordGenerator";
 
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function CustomerForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Customer');
  const [customer, setCustomer] = useState({
    id: null,
    customer_code: '',
    first_name: '',
    last_name: '',
    email: '',
    customer_pass: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    date_of_birth: '',
    gender: '',
    notes: '',
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);

  // Load customer data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/customer-management/customers/${id}`)
        .then(({ data }) => {
          const customerData = data.data || data;
          setCustomer(customerData);
          setIsLoading(false);
          setIsActive(customerData.active);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false); // Ensure loading state is cleared
        });
    }
  }, [id]);

  // Handle form submission
  const onSubmit = (ev) => {
    ev.preventDefault();
    
    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...customer,
      active: isActive,
    };

    const request = customer.id
      ? axiosClient.put(`/customer-management/customers/${customer.id}`, submitData)
      : axiosClient.post('/customer-management/customers', submitData);

    request
      .then(() => {
        const action = customer.id ? 'updated' : 'added';
        toastAction.current.showToast(`Customer has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/customer-management/customers'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false); // Ensure loading state is cleared
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!customer.id) return;
    
    if (window.confirm('Are you sure you want to delete this customer?')) {
      setIsLoading(true);
      axiosClient.delete(`/customer-management/customers/${customer.id}`)
        .then(() => {
          toastAction.current.showToast('Customer has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/customer-management/customers'), 2000);
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
            {customer.id ? 'Edit Customer' : 'Create New Customer'}
          </h4>
          {!customer.id && <p className="tip-message">Create a new customer and add them to this site.</p>}
        </div>
        <div className="card-body">
          {/* Customer Code Field */}
          <Field
            label="Customer Code"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.customer_code || ''}
                onChange={ev => setCustomer({ ...customer, customer_code: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* First Name Field */}
          <Field
            label="First Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.first_name || ''}
                onChange={ev => setCustomer({ ...customer, first_name: DOMPurify.sanitize(ev.target.value) })}
                required
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Last Name Field */}
          <Field
            label="Last Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.last_name || ''}
                onChange={ev => setCustomer({ ...customer, last_name: DOMPurify.sanitize(ev.target.value) })}
                required
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Email Field */}
          <Field
            label="Email"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="email"
                value={customer.email || ''}
                onChange={ev => setCustomer({ ...customer, email: DOMPurify.sanitize(ev.target.value) })}
                required
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Password Field */}
          <PasswordGenerator 
            label="Password"
            setUser={setCustomer} 
            user={customer}
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Phone Field */}
          <Field
            label="Phone"
            inputComponent={
              <input
                className="form-control"
                type="tel"
                value={customer.phone || ''}
                onChange={ev => setCustomer({ ...customer, phone: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Address Field */}
          <Field
            label="Address"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={customer.address || ''}
                onChange={ev => setCustomer({ ...customer, address: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* City Field */}
          <Field
            label="City"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.city || ''}
                onChange={ev => setCustomer({ ...customer, city: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* State Field */}
          <Field
            label="State/Province"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.state || ''}
                onChange={ev => setCustomer({ ...customer, state: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Postal Code Field */}
          <Field
            label="Postal Code"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.postal_code || ''}
                onChange={ev => setCustomer({ ...customer, postal_code: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Country Field */}
          <Field
            label="Country"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={customer.country || ''}
                onChange={ev => setCustomer({ ...customer, country: DOMPurify.sanitize(ev.target.value) })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Date of Birth Field */}
          <Field
            label="Date of Birth"
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={customer.date_of_birth || ''}
                onChange={ev => setCustomer({ ...customer, date_of_birth: ev.target.value })}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          {/* Gender Field */}
          <Field
            label="Gender"
            inputComponent={
              <select
                className="form-select"
                value={customer.gender || ''}
                onChange={ev => setCustomer({ ...customer, gender: ev.target.value })}
              >
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
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
                value={customer.notes || ''}
                onChange={ev => setCustomer({ ...customer, notes: DOMPurify.sanitize(ev.target.value) })}
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
            <Link type="button" to="/customer-management/customers" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
              Cancel
            </Link> &nbsp;
            <button type="submit" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          {customer.id && (
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
