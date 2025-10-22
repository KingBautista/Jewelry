import { useState, useEffect, useRef } from 'react';
import { useStateContext } from '../contexts/AuthProvider';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';

const Header = ({ user, onMenuClick, sidebarOpen }) => {
  const { logout } = useStateContext();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const dropdownRef = useRef(null);

  const handleLogout = () => {
    logout();
  };

  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  // Function to get user initials
  const getUserInitials = (user) => {
    if (!user) return 'CU';
    
    // Handle both user.data and direct user object
    const userData = user.data || user;
    
    // Try to get first and last name from user object
    const firstName = userData.first_name || '';
    const lastName = userData.last_name || '';
    
    if (firstName && lastName) {
      return `${firstName.charAt(0).toUpperCase()}${lastName.charAt(0).toUpperCase()}`;
    } else if (firstName) {
      return firstName.charAt(0).toUpperCase();
    } else if (userData.user_login) {
      // Fallback to username if no first/last name
      return userData.user_login.charAt(0).toUpperCase();
    } else {
      return 'CU'; // Customer User
    }
  };

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsDropdownOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  return (
    <header className="navbar navbar-expand-lg navbar-light bg-champagne shadow-sm">
      <div className="container-fluid">
        <div className="d-flex align-items-center">
          {/* Mobile menu button */}
          <button 
            className="btn btn-link d-lg-none me-2 p-2" 
            onClick={onMenuClick}
            style={{ color: 'var(--text-color)', border: 'none' }}
            aria-label="Toggle navigation"
          >
            <FontAwesomeIcon 
              icon={sidebarOpen ? solidIconMap.times : solidIconMap.bars} 
              style={{ fontSize: '1.25rem' }} 
            />
          </button>
          
          <FontAwesomeIcon icon={solidIconMap.gem} className="text-champagne me-2" style={{ fontSize: '1.5rem' }} />
          <span className="navbar-brand mb-0 h1">Customer Portal</span>
        </div>
        
        <div className="d-flex align-items-center">
          <div className="dropdown" ref={dropdownRef}>
            <button 
              className="btn btn-link dropdown-toggle text-decoration-none d-flex align-items-center" 
              type="button" 
              onClick={toggleDropdown}
              style={{ color: 'var(--text-color)' }}
            >
              <div 
                className="rounded-circle d-flex align-items-center justify-content-center me-2" 
                style={{ 
                  width: '32px', 
                  height: '32px', 
                  backgroundColor: 'var(--champagne-primary)', 
                  color: 'white',
                  fontSize: '0.875rem',
                  fontWeight: 'bold',
                  flexShrink: 0
                }}
              >
                {getUserInitials(user)}
              </div>
              <span className="d-none d-sm-inline text-truncate" style={{ maxWidth: '150px' }}>
                {user?.data?.full_name || user?.data?.user_login || user?.full_name || user?.user_login || 'Customer'}
              </span>
            </button>
            <ul className={`dropdown-menu dropdown-menu-end ${isDropdownOpen ? 'show' : ''}`} style={{ marginRight: isDropdownOpen ? '1rem' : '0' }}>
              <li>
                <a className="dropdown-item" href="/profile">
                  <FontAwesomeIcon icon={solidIconMap.user} className="me-2" />
                  Profile
                </a>
              </li>
              <li><hr className="dropdown-divider" /></li>
              <li>
                <button className="dropdown-item" onClick={handleLogout}>
                  <FontAwesomeIcon icon={solidIconMap.signOut} className="me-2" />
                  Logout
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;
