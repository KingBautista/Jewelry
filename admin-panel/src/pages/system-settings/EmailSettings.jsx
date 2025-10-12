import { useState, useEffect, useRef } from "react";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';
import axiosClient from '../../axios-client';
import ToastMessage from '../../components/ToastMessage';
import { useAccess } from '../../hooks/useAccess';

export default function EmailSettings() {
  const accessHelper = useAccess();
  const access = accessHelper.hasAccess();

  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [settings, setSettings] = useState({
    mail_from_address: '',
    mail_from_name: '',
    mail_reply_to_address: '',
    mail_reply_to_name: '',
    admin_email: '',
    admin_name: ''
  });
  const [errors, setErrors] = useState({});
  const toastAction = useRef();

  useEffect(() => {
    fetchEmailSettings();
  }, []);

  const fetchEmailSettings = async () => {
    try {
      setLoading(true);
      const response = await axiosClient.get('/system-settings/email-settings/config/all');
      if (response.data.success) {
        setSettings(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch email settings:', error);
      toastAction.current.showToast('Failed to load email settings', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setSettings(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: null
      }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!settings.mail_from_address.trim()) {
      newErrors.mail_from_address = 'From email address is required';
    } else if (!/\S+@\S+\.\S+/.test(settings.mail_from_address)) {
      newErrors.mail_from_address = 'Please enter a valid email address';
    }

    if (!settings.mail_from_name.trim()) {
      newErrors.mail_from_name = 'From name is required';
    }

    if (!settings.admin_email.trim()) {
      newErrors.admin_email = 'Admin email is required';
    } else if (!/\S+@\S+\.\S+/.test(settings.admin_email)) {
      newErrors.admin_email = 'Please enter a valid email address';
    }

    if (!settings.admin_name.trim()) {
      newErrors.admin_name = 'Admin name is required';
    }

    if (settings.mail_reply_to_address && !/\S+@\S+\.\S+/.test(settings.mail_reply_to_address)) {
      newErrors.mail_reply_to_address = 'Please enter a valid email address';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      toastAction.current.showToast('Please fix the validation errors', 'error');
      return;
    }

    try {
      setSaving(true);
      const response = await axiosClient.post('/system-settings/email-settings/config/update', settings);
      
      if (response.data.success) {
        toastAction.current.showToast('Email settings updated successfully', 'success');
      } else {
        toastAction.current.showToast(response.data.message || 'Failed to update settings', 'error');
      }
    } catch (error) {
      console.error('Failed to update email settings:', error);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
        toastAction.current.showToast('Please fix the validation errors', 'error');
      } else {
        toastAction.current.showToast(
          error.response?.data?.message || 'Failed to update email settings', 
          'error'
        );
      }
    } finally {
      setSaving(false);
    }
  };

  const handleReset = () => {
    fetchEmailSettings();
    setErrors({});
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ height: '400px' }}>
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Loading...</span>
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="card mb-0">
        <div className="card-header d-flex justify-content-between align-items-center border-0 py-2">
          <h5 className="mb-2 mt-2">Email Settings</h5>
        </div>
        
        <div className="card-body">
          <div className="row">
            <div className="col-lg-8">
              <form onSubmit={handleSubmit}>
                <div className="mb-4">
                  <h6 className="fw-bold mb-3">
                    <FontAwesomeIcon icon={solidIconMap.envelope} className="me-2 text-primary" />
                    Email Configuration
                  </h6>
                  <p className="mb-4" style={{ color: '#6c757d' }}>
                    Configure email settings that replace the values from your .env file. These settings will be used for all outgoing emails from the system.
                  </p>
                </div>

                <div className="row g-3">
                  <div className="col-12">
                    <h6 className="fw-bold mb-3 text-primary">Outgoing Email Settings</h6>
                  </div>
                  
                  <div className="col-md-6">
                    <label htmlFor="mail_from_address" className="form-label">
                      From Email Address <span className="text-danger">*</span>
                    </label>
                    <input
                      type="email"
                      className={`form-control ${errors.mail_from_address ? 'is-invalid' : ''}`}
                      id="mail_from_address"
                      name="mail_from_address"
                      value={settings.mail_from_address}
                      onChange={handleInputChange}
                      placeholder="noreply@example.com"
                      disabled={saving}
                    />
                    {errors.mail_from_address && (
                      <div className="invalid-feedback">
                        {errors.mail_from_address}
                      </div>
                    )}
                    <div style={{ fontSize: '0.875em', color: '#6c757d', marginTop: '0.25rem' }}>
                      Primary email address for outgoing emails (replaces MAIL_FROM_ADDRESS).
                    </div>
                  </div>

                  <div className="col-md-6">
                    <label htmlFor="mail_from_name" className="form-label">
                      From Display Name <span className="text-danger">*</span>
                    </label>
                    <input
                      type="text"
                      className={`form-control ${errors.mail_from_name ? 'is-invalid' : ''}`}
                      id="mail_from_name"
                      name="mail_from_name"
                      value={settings.mail_from_name}
                      onChange={handleInputChange}
                      placeholder="Jewelry Management System"
                      disabled={saving}
                    />
                    {errors.mail_from_name && (
                      <div className="invalid-feedback">
                        {errors.mail_from_name}
                      </div>
                    )}
                    <div style={{ fontSize: '0.875em', color: '#6c757d', marginTop: '0.25rem' }}>
                      Display name for outgoing emails (replaces MAIL_FROM_NAME).
                    </div>
                  </div>

                  <div className="col-md-6">
                    <label htmlFor="mail_reply_to_address" className="form-label">
                      Reply-To Email Address
                    </label>
                    <input
                      type="email"
                      className={`form-control ${errors.mail_reply_to_address ? 'is-invalid' : ''}`}
                      id="mail_reply_to_address"
                      name="mail_reply_to_address"
                      value={settings.mail_reply_to_address}
                      onChange={handleInputChange}
                      placeholder="support@example.com"
                      disabled={saving}
                    />
                    {errors.mail_reply_to_address && (
                      <div className="invalid-feedback">
                        {errors.mail_reply_to_address}
                      </div>
                    )}
                    <div style={{ fontSize: '0.875em', color: '#6c757d', marginTop: '0.25rem' }}>
                      Optional reply-to address for email communications.
                    </div>
                  </div>

                  <div className="col-md-6">
                    <label htmlFor="mail_reply_to_name" className="form-label">
                      Reply-To Display Name
                    </label>
                    <input
                      type="text"
                      className={`form-control ${errors.mail_reply_to_name ? 'is-invalid' : ''}`}
                      id="mail_reply_to_name"
                      name="mail_reply_to_name"
                      value={settings.mail_reply_to_name}
                      onChange={handleInputChange}
                      placeholder="Support Team"
                      disabled={saving}
                    />
                    {errors.mail_reply_to_name && (
                      <div className="invalid-feedback">
                        {errors.mail_reply_to_name}
                      </div>
                    )}
                    <div style={{ fontSize: '0.875em', color: '#6c757d', marginTop: '0.25rem' }}>
                      Optional reply-to display name.
                    </div>
                  </div>

                  <div className="col-12 mt-4">
                    <h6 className="fw-bold mb-3 text-primary">Admin Notification Settings</h6>
                  </div>

                  <div className="col-md-6">
                    <label htmlFor="admin_email" className="form-label">
                      Admin Email Address <span className="text-danger">*</span>
                    </label>
                    <input
                      type="email"
                      className={`form-control ${errors.admin_email ? 'is-invalid' : ''}`}
                      id="admin_email"
                      name="admin_email"
                      value={settings.admin_email}
                      onChange={handleInputChange}
                      placeholder="admin@example.com"
                      disabled={saving}
                    />
                    {errors.admin_email && (
                      <div className="invalid-feedback">
                        {errors.admin_email}
                      </div>
                    )}
                    <div style={{ fontSize: '0.875em', color: '#6c757d', marginTop: '0.25rem' }}>
                      Email address for admin notifications and system alerts.
                    </div>
                  </div>

                  <div className="col-md-6">
                    <label htmlFor="admin_name" className="form-label">
                      Admin Display Name <span className="text-danger">*</span>
                    </label>
                    <input
                      type="text"
                      className={`form-control ${errors.admin_name ? 'is-invalid' : ''}`}
                      id="admin_name"
                      name="admin_name"
                      value={settings.admin_name}
                      onChange={handleInputChange}
                      placeholder="System Administrator"
                      disabled={saving}
                    />
                    {errors.admin_name && (
                      <div className="invalid-feedback">
                        {errors.admin_name}
                      </div>
                    )}
                    <div style={{ fontSize: '0.875em', color: '#6c757d', marginTop: '0.25rem' }}>
                      Display name for admin notifications.
                    </div>
                  </div>
                </div>

                <div className="mt-4 pt-3 border-top">
                  <div className="d-flex gap-2">
                    <button
                      type="submit"
                      className="btn btn-primary"
                      disabled={saving}
                    >
                      {saving ? (
                        <>
                          <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                          Saving...
                        </>
                      ) : (
                        <>
                          <FontAwesomeIcon icon={solidIconMap.floppyDisk} className="me-2" />
                          Save Settings
                        </>
                      )}
                    </button>
                    <button
                      type="button"
                      className="btn btn-outline-secondary"
                      onClick={handleReset}
                      disabled={saving}
                    >
                      <FontAwesomeIcon icon={solidIconMap.arrowRotateLeft} className="me-2" />
                      Reset
                    </button>
                  </div>
                </div>
              </form>
            </div>

            <div className="col-lg-4">
              <div className="card bg-light-subtle">
                <div className="card-body">
                  <h6 className="card-title">
                    <FontAwesomeIcon icon={solidIconMap.infoCircle} className="me-2 text-info" />
                    Information
                  </h6>
                  <div className="small" style={{ color: '#6c757d' }}>
                    <p className="mb-2">
                      <strong>From Email:</strong> This replaces MAIL_FROM_ADDRESS from your .env file. All outgoing emails will use this address.
                    </p>
                    <p className="mb-2">
                      <strong>From Name:</strong> This replaces MAIL_FROM_NAME from your .env file. This name appears as the sender.
                    </p>
                    <p className="mb-2">
                      <strong>Reply-To:</strong> Optional reply-to address and name for email communications.
                    </p>
                    <p className="mb-0">
                      <strong>Admin Settings:</strong> Email address and name for admin notifications and system alerts.
                    </p>
                  </div>
                </div>
              </div>

              <div className="card bg-warning-subtle mt-3">
                <div className="card-body">
                  <h6 className="card-title text-warning">
                    <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" />
                    Important Notes
                  </h6>
                  <div className="small" style={{ color: '#6c757d' }}>
                    <ul className="mb-0">
                      <li>These settings replace the email configuration in your .env file</li>
                      <li>Changes will affect all outgoing emails from the system</li>
                      <li>Make sure the email addresses are valid and accessible</li>
                      <li>Test email functionality after making changes</li>
                      <li>SMTP settings (host, port, credentials) still come from .env</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <ToastMessage ref={toastAction} />
    </>
  );
}
