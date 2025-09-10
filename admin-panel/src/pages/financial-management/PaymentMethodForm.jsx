import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function PaymentMethodForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Payment Method');
  const [paymentMethod, setPaymentMethod] = useState({
    id: null,
    bank_name: '',
    account_name: '',
    account_number: '',
    description: '',
    qr_code_image: null,
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [qrCodeFile, setQrCodeFile] = useState(null);
  const [qrCodePreview, setQrCodePreview] = useState(null);

  // Load payment method data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/financial-management/payment-methods/${id}`)
        .then(({ data }) => {
          const paymentMethodData = data.data || data;
          setPaymentMethod(paymentMethodData);
          setIsLoading(false);
          setIsActive(paymentMethodData.active);
          if (paymentMethodData.qr_code_image) {
            setQrCodePreview(paymentMethodData.qr_code_image);
          }
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  }, [id]);

  // Handle file upload
  const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
      // Validate file type
      if (!file.type.startsWith('image/')) {
        toastAction.current.showToast('Please select a valid image file', 'warning');
        return;
      }

      // Validate file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        toastAction.current.showToast('File size must be less than 5MB', 'warning');
        return;
      }

      setQrCodeFile(file);
      
      // Create preview
      const reader = new FileReader();
      reader.onload = (e) => {
        setQrCodePreview(e.target.result);
      };
      reader.readAsDataURL(file);
    }
  };

  // Remove QR code image
  const removeQrCode = () => {
    setQrCodeFile(null);
    setQrCodePreview(null);
    setPaymentMethod({ ...paymentMethod, qr_code_image: null });
  };

  // Handle form submission
  const onSubmit = (ev) => {
    ev.preventDefault();
    
    // Validate required fields
    if (!paymentMethod.bank_name || !paymentMethod.account_name || !paymentMethod.account_number) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    // Validate account number (basic validation)
    if (paymentMethod.account_number.length < 10) {
      toastAction.current.showToast('Account number must be at least 10 characters long', 'warning');
      return;
    }

    setIsLoading(true);

    // Prepare form data for file upload
    const formData = new FormData();
    formData.append('bank_name', DOMPurify.sanitize(paymentMethod.bank_name));
    formData.append('account_name', DOMPurify.sanitize(paymentMethod.account_name));
    formData.append('account_number', DOMPurify.sanitize(paymentMethod.account_number));
    formData.append('description', DOMPurify.sanitize(paymentMethod.description || ''));
    formData.append('active', isActive ? '1' : '0');
    
    if (qrCodeFile) {
      formData.append('qr_code_image', qrCodeFile);
    }

    const request = paymentMethod.id
      ? (() => {
          formData.append('_method', 'PUT');
          return axiosClient.post(`/financial-management/payment-methods/${paymentMethod.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
        })()
      : axiosClient.post('/financial-management/payment-methods', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

    request
      .then(() => {
        const action = paymentMethod.id ? 'updated' : 'added';
        toastAction.current.showToast(`Payment method has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/financial-management/payment-methods'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!paymentMethod.id) return;
    
    if (window.confirm('Are you sure you want to delete this payment method?')) {
      setIsLoading(true);
      axiosClient.delete(`/financial-management/payment-methods/${paymentMethod.id}`)
        .then(() => {
          toastAction.current.showToast('Payment method has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/financial-management/payment-methods'), 2000);
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
            {paymentMethod.id ? 'Edit Payment Method' : 'Create New Payment Method'}
          </h4>
          {!paymentMethod.id && <p className="tip-message">Create a new payment method for your jewelry business transactions.</p>}
        </div>
        <div className="card-body">
          {/* Bank Name Field */}
          <Field
            label="Bank Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentMethod.bank_name}
                onChange={ev => setPaymentMethod({ ...paymentMethod, bank_name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., BDO, BPI, Metrobank"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Account Name Field */}
          <Field
            label="Account Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentMethod.account_name}
                onChange={ev => setPaymentMethod({ ...paymentMethod, account_name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., John Doe, Jewelry Store Inc."
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Account Number Field */}
          <Field
            label="Account Number"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentMethod.account_number}
                onChange={ev => setPaymentMethod({ ...paymentMethod, account_number: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., 1234567890"
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
                value={paymentMethod.description}
                onChange={ev => setPaymentMethod({ ...paymentMethod, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Optional description for this payment method"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* QR Code Image Field */}
          <div className="row mb-3">
            <div className="col-sm-12 col-md-3">
              <label className="form-label">QR Code Image</label>
              <small className="form-text text-muted d-block">Optional QR code for easy payments</small>
            </div>
            <div className="col-sm-12 col-md-9">
              <div className="border rounded p-3">
                <div className="mb-3">
                  <input
                    type="file"
                    className="form-control"
                    accept="image/*"
                    onChange={handleFileUpload}
                  />
                  <small className="form-text text-muted">
                    Supported formats: JPG, PNG, GIF. Max size: 5MB
                  </small>
                </div>
                
                {qrCodePreview && (
                  <div className="qr-code-preview">
                    <div className="d-flex align-items-center justify-content-between mb-2">
                      <h6 className="mb-0">QR Code Preview</h6>
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        onClick={removeQrCode}
                      >
                        <FontAwesomeIcon icon={solidIconMap.trash} className="me-1" />
                        Remove
                      </button>
                    </div>
                    <div className="text-center">
                      <img
                        src={qrCodePreview}
                        alt="QR Code Preview"
                        className="img-thumbnail"
                        style={{ maxWidth: '200px', maxHeight: '200px' }}
                      />
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
          
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
            <Link type="button" to="/financial-management/payment-methods" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
              Cancel
            </Link> &nbsp;
            <button type="submit" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          {paymentMethod.id && (
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
