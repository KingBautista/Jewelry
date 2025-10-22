import { useState, useEffect } from 'react';
import axiosClient from '../axios-client';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';
import LoadingSpinner from '../components/LoadingSpinner';

const Profile = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [user, setUser] = useState(null);
  const [formData, setFormData] = useState({
    user_login: '',
    user_email: '',
    phone: '',
    address: ''
  });
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState('');

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await axiosClient.get('/customer/user');
      const userData = response.data.data;
      setUser(userData);
      setFormData({
        user_login: userData.user_login || '',
        user_email: userData.user_email || '',
        phone: userData.phone || '',
        address: userData.address || ''
      });
    } catch (error) {
      console.error('Error fetching profile:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: null
      }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setMessage('');

    try {
      console.log('Sending profile update:', formData);
      const response = await axiosClient.put('/customer/user', formData);
      console.log('Profile update response:', response.data);
      setUser(response.data.data);
      setMessage('Profile updated successfully!');
    } catch (error) {
      console.error('Profile update error:', error);
      console.error('Error response:', error.response?.data);
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({ general: ['Profile update failed. Please try again.'] });
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <div className="container-fluid">
      <div className="row">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center mb-4">
            <h1 className="h3 mb-0" style={{ color: 'var(--text-color)' }}>
              <FontAwesomeIcon icon={solidIconMap.userCircle} className="me-2 text-champagne" />
              My Profile
            </h1>
          </div>
        </div>
      </div>

      <div className="row g-4">
        <div className="col-12 col-lg-8">
          <div className="card shadow-sm">
            <div className="card-header bg-champagne">
              <h5 className="mb-0">Profile Information</h5>
            </div>
            <div className="card-body">
              <form onSubmit={handleSubmit}>
                {/* Success Message */}
                {message && (
                  <div className="alert alert-success">
                    <div className="d-flex align-items-center">
                      <FontAwesomeIcon icon={solidIconMap.checkCircle} className="me-2" />
                      <span>{message}</span>
                    </div>
                  </div>
                )}

                {/* Error Messages */}
                {Object.keys(errors).length > 0 && (
                  <div className="alert alert-danger">
                    <div className="d-flex align-items-center mb-2">
                      <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" />
                      <span className="fw-medium">Please fix the following errors:</span>
                    </div>
                    {Object.keys(errors).map(key => (
                      <div key={key} className="ms-4">• {errors[key]}</div>
                    ))}
                  </div>
                )}

                {/* Username */}
                <div className="mb-4">
                  <label htmlFor="user_login" className="form-label fw-semibold">
                    Username *
                  </label>
                  <input
                    type="text"
                    id="user_login"
                    name="user_login"
                    className="form-control"
                    value={formData.user_login}
                    onChange={handleInputChange}
                    required
                  />
                </div>

                {/* Email */}
                <div className="mb-4">
                  <label htmlFor="user_email" className="form-label fw-semibold">
                    Email Address *
                  </label>
                  <input
                    type="email"
                    id="user_email"
                    name="user_email"
                    className="form-control"
                    value={formData.user_email}
                    onChange={handleInputChange}
                    required
                  />
                </div>

                {/* Phone */}
                <div className="mb-4">
                  <label htmlFor="phone" className="form-label fw-semibold">
                    Phone Number
                  </label>
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    className="form-control"
                    value={formData.phone}
                    onChange={handleInputChange}
                    placeholder="Enter your phone number"
                  />
                </div>

                {/* Address */}
                <div className="mb-4">
                  <label htmlFor="address" className="form-label fw-semibold">
                    Address
                  </label>
                  <textarea
                    id="address"
                    name="address"
                    className="form-control"
                    rows="3"
                    value={formData.address}
                    onChange={handleInputChange}
                    placeholder="Enter your address"
                  />
                </div>

                {/* Submit Button */}
                <div className="d-flex flex-column flex-sm-row gap-2">
                  <button
                    type="submit"
                    className="btn btn-primary flex-fill"
                    disabled={saving}
                  >
                    {saving ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span className="d-none d-sm-inline">Saving...</span>
                        <span className="d-sm-none">Saving...</span>
                      </>
                    ) : (
                      <>
                        <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
                        <span className="d-none d-sm-inline">Save Changes</span>
                        <span className="d-sm-none">Save</span>
                      </>
                    )}
                  </button>
                  <button
                    type="button"
                    className="btn btn-outline-secondary flex-fill"
                    onClick={fetchProfile}
                  >
                    <FontAwesomeIcon icon={solidIconMap.undo} className="me-2" />
                    <span className="d-none d-sm-inline">Reset</span>
                    <span className="d-sm-none">Reset</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        {/* Account Information */}
        <div className="col-12 col-lg-4">
          <div className="card shadow-sm">
            <div className="card-header bg-info text-white">
              <h5 className="mb-0">
                <FontAwesomeIcon icon={solidIconMap.infoCircle} className="me-2" />
                Account Information
              </h5>
            </div>
            <div className="card-body">
              <div className="mb-3">
                <div className="d-flex justify-content-between align-items-center">
                  <h6 className="fw-semibold mb-0">Account Status</h6>
                  <span className={`badge ${user?.user_status ? 'badge-success' : 'badge-warning'}`}>
                    {user?.user_status ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
              
              <div className="mb-3">
                <h6 className="fw-semibold">Member Since</h6>
                <p className="text-muted mb-0">
                  {user?.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
                </p>
              </div>
              
              <div className="mb-3">
                <h6 className="fw-semibold">Last Updated</h6>
                <p className="text-muted mb-0">
                  {user?.updated_at ? new Date(user.updated_at).toLocaleDateString() : 'N/A'}
                </p>
              </div>
            </div>
          </div>

          <div className="card shadow-sm mt-3 mt-lg-4">
            <div className="card-header bg-warning text-dark">
              <h5 className="mb-0">
                <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" />
                Security Notice
              </h5>
            </div>
            <div className="card-body">
              <ul className="mb-0 small">
                <li>Keep your login credentials secure</li>
                <li>Use a strong, unique password</li>
                <li>Contact support if you notice any suspicious activity</li>
                <li>Your email is used for important notifications</li>
              </ul>
            </div>
          </div>

          <div className="card shadow-sm mt-3 mt-lg-4">
            <div className="card-header bg-success text-white">
              <h5 className="mb-0">
                <FontAwesomeIcon icon={solidIconMap.phone} className="me-2" />
                Need Help?
              </h5>
            </div>
            <div className="card-body">
              <p className="mb-2 small">If you need assistance with your account:</p>
              <ul className="mb-0 small">
                <li>Contact customer support</li>
                <li>Use the forgot password feature</li>
                <li>Check your email for notifications</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Profile;
