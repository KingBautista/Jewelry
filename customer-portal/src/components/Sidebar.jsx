import { NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';

const Sidebar = ({ isOpen, onClose }) => {
  const menuItems = [
    {
      name: 'Dashboard',
      path: '/dashboard',
      icon: solidIconMap.chartLine
    },
    {
      name: 'My Invoices',
      path: '/invoices',
      icon: solidIconMap.fileInvoice
    },
    {
      name: 'Submit Payment',
      path: '/payment-submission',
      icon: solidIconMap.creditCard
    },
    {
      name: 'Payment History',
      path: '/payment-history',
      icon: solidIconMap.history
    }
  ];

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div 
          className="d-lg-none position-fixed w-100 h-100" 
          style={{ 
            top: 0, 
            left: 0, 
            backgroundColor: 'rgba(0,0,0,0.5)', 
            zIndex: 1040 
          }}
          onClick={onClose}
        />
      )}
      
      <nav 
        className={`sidebar d-flex flex-column ${isOpen ? 'show' : ''}`} 
        style={{ width: '280px', minHeight: '100vh' }}
      >
        <div className="p-3">
          <div className="d-flex align-items-center justify-content-between mb-4">
            <div className="d-flex align-items-center">
              <FontAwesomeIcon icon={solidIconMap.gem} className="text-champagne me-2" style={{ fontSize: '1.5rem' }} />
              <span className="fw-bold" style={{ color: 'var(--text-color)' }}>Il.llussso</span>
            </div>
            
            {/* Mobile close button */}
            <button 
              className="btn btn-link d-lg-none p-1" 
              onClick={onClose}
              style={{ color: 'var(--text-color)', border: 'none' }}
              aria-label="Close navigation"
            >
              <FontAwesomeIcon icon={solidIconMap.times} style={{ fontSize: '1.25rem' }} />
            </button>
          </div>
          
          <ul className="nav nav-pills flex-column">
            {menuItems.map((item, index) => (
              <li key={index} className="nav-item">
                <NavLink 
                  to={item.path}
                  className={({ isActive }) => 
                    `nav-link ${isActive ? 'active' : ''}`
                  }
                  onClick={() => {
                    // Close sidebar on mobile when link is clicked
                    if (window.innerWidth <= 768) {
                      onClose();
                    }
                  }}
                >
                  <FontAwesomeIcon icon={item.icon} className="me-2" />
                  {item.name}
                </NavLink>
              </li>
            ))}
          </ul>
        </div>
      </nav>
    </>
  );
};

export default Sidebar;
