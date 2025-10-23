import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import PasswordGenerator from "../../components/PasswordGenerator";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function Profile() {
  const toastAction = useRef();
  const [isLoading, setIsLoading] = useState(false);
  const [user, setUser] = useState({
    id: null,
    user_login: '',
    user_email: '',
    user_pass: '',
    first_name: '',
    last_name: '',
    nickname: '',
    biography: '',
    theme: '',
  });

  const onSubmit = async (ev) => {
    ev.preventDefault();
    setIsLoading(true);

    try {
      // Prepare the data for submission - only include password if it's not empty
      const updatedProfile = {
        user_login: user.user_login,
        user_email: user.user_email,
        first_name: user.first_name,
        last_name: user.last_name,
        nickname: user.nickname,
        biography: user.biography,
        theme: user.theme
      };

      // Only include password if it's provided and not empty
      if (user.user_pass && user.user_pass.trim() !== '') {
        updatedProfile.user_pass = user.user_pass;
      }
    
      const apiUrl = '/profile';
      const method = 'post';
    
      // Make the API call
      const response = await axiosClient[method](apiUrl, updatedProfile);
  
      const successMessage = response.data.message || 'Profile has been updated successfully.';
      toastAction.current.showToast(successMessage, 'success');
  
      // Update the user state with the response data
      if (response.data.user) {
        setUser(prev => ({
          ...prev,
          ...response.data.user,
          first_name: response.data.user.first_name || response.data.user.user_details?.first_name || '',
          last_name: response.data.user.last_name || response.data.user.user_details?.last_name || '',
          nickname: response.data.user.nickname || response.data.user.user_details?.nickname || '',
          biography: response.data.user.biography || response.data.user.user_details?.biography || '',
          theme: response.data.user.theme || response.data.user.user_details?.theme || '',
          user_pass: '', // Clear password field after successful update
        }));
      }
  
      setIsLoading(false);
    } catch (errors) {
      console.error('Profile update error:', errors);
      toastAction.current.showError(errors.response);
      setIsLoading(false);
    }
  };

  const handleSelectedTheme = (theme) => {
    // Set the theme attribute on the document
    document.documentElement.setAttribute('data-coreui-theme', theme);
    // Save the theme to localStorage so it persists across page reloads
    localStorage.setItem('theme', theme);
    // Update user state
    setUser(prev => ({ ...prev, theme }));
  };

  // execute once component is loaded
  useEffect(() => {
    axiosClient.get('/user')
    .then(({data}) => {
      const userData = data; 
      setUser({
        id: userData.id,
        user_login: userData.user_login || '',
        user_email: userData.user_email || '',
        user_pass: '',
        first_name: userData.first_name || userData.user_details?.first_name || '',
        last_name: userData.last_name || userData.user_details?.last_name || '',
        nickname: userData.nickname || userData.user_details?.nickname || '',
        biography: userData.biography || userData.user_details?.biography || '',
        theme: userData.theme || userData.user_details?.theme || '',
      });
    })
    .catch(error => {
      console.error('Failed to fetch user data:', error);
      toastAction.current.showToast('Failed to load profile data', 'error');
    });
  }, []); // empty array means 'run once'

  return (
    <>
    <div className="card">
      <form onSubmit={onSubmit}>
        <div className="card-header">
          <h4>
            <FontAwesomeIcon icon={solidIconMap.user} className="me-2" />
            Profile Settings
          </h4>
          <p className="tip-message">Update your personal information and preferences.</p>
        </div>
        <div className="card-body">
          {/* Username Field */}
          <Field
            label="Username"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={user.user_login}
                onChange={ev => setUser({ ...user, user_login: DOMPurify.sanitize(ev.target.value) })}
                disabled
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />
          
          {/* Email Field */}
          <Field
            label="Email Address"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="email"
                value={user.user_email}
                onChange={ev => setUser({ ...user, user_email: DOMPurify.sanitize(ev.target.value) })}
                required
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* First Name Field */}
          <Field
            label="First Name"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={user.first_name}
                onChange={ev => setUser({ ...user, first_name: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Enter your first name"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Last Name Field */}
          <Field
            label="Last Name"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={user.last_name}
                onChange={ev => setUser({ ...user, last_name: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Enter your last name"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Nickname Field */}
          <Field
            label="Nickname"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={user.nickname}
                onChange={ev => setUser({ ...user, nickname: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Enter your nickname"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Theme Field */}
          <Field
            label="Theme Preference"
            inputComponent={
              <select 
                className="form-select" 
                value={user?.theme} 
                onChange={ev => handleSelectedTheme(ev.target.value)}
              >
                <option value="">Select Theme</option>
                <option value="light">Light Theme</option>
                <option value="dark">Dark Theme</option>
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Biography Field */}
          <Field
            label="Biography"
            inputComponent={
              <textarea 
                className="form-control" 
                rows={4} 
                value={user.biography || ''} 
                onChange={ev => setUser({...user, biography: ev.target.value})}
                placeholder="Share a little biographical information to fill out your profile..."
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Password Field - Not Required */}
          <PasswordGenerator 
            label="New Password"
            setUser={setUser} 
            user={user}
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

        </div>
        <div className="card-footer">
          <div className="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            <div className="d-flex flex-column flex-sm-row gap-2">
              <button type="submit" className="btn btn-secondary w-100 w-sm-auto">
                <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
                {isLoading ? 'Saving Changes...' : 'Save Profile'} &nbsp;
                {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
    <ToastMessage ref={toastAction} />
    </>
  );
}