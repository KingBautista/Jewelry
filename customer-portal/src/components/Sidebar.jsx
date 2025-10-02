import { NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';

const Sidebar = () => {
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
    <nav className="sidebar d-flex flex-column" style={{ width: '250px', minHeight: '100vh' }}>
      <div className="p-3">
        <div className="d-flex align-items-center mb-4">
          <FontAwesomeIcon icon={solidIconMap.gem} className="text-champagne me-2" style={{ fontSize: '1.5rem' }} />
          <span className="fw-bold" style={{ color: 'var(--text-color)' }}>Jewelry Portal</span>
        </div>
        
        <ul className="nav nav-pills flex-column">
          {menuItems.map((item, index) => (
            <li key={index} className="nav-item">
              <NavLink 
                to={item.path}
                className={({ isActive }) => 
                  `nav-link ${isActive ? 'active' : ''}`
                }
              >
                <FontAwesomeIcon icon={item.icon} className="me-2" />
                {item.name}
              </NavLink>
            </li>
          ))}
        </ul>
      </div>
    </nav>
  );
};

export default Sidebar;
