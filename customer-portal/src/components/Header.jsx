import { useState, useEffect, useRef } from 'react';
import { useStateContext } from '../contexts/AuthProvider';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';

const Header = ({ user }) => {
  const { logout } = useStateContext();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const dropdownRef = useRef(null);

  const handleLogout = () => {
    logout();
  };

  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
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
          <FontAwesomeIcon icon={solidIconMap.gem} className="text-champagne me-2" style={{ fontSize: '1.5rem' }} />
          <span className="navbar-brand mb-0 h1">Customer Portal</span>
        </div>
        
        <div className="d-flex align-items-center">
          <div className="dropdown" ref={dropdownRef}>
            <button 
              className="btn btn-link dropdown-toggle text-decoration-none" 
              type="button" 
              onClick={toggleDropdown}
              style={{ color: 'var(--text-color)' }}
            >
              <FontAwesomeIcon icon={solidIconMap.user} className="me-2" />
              {user?.user_login || 'Customer'}
            </button>
            <ul className={`dropdown-menu dropdown-menu-end ${isDropdownOpen ? 'show' : ''}`}>
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
