import { Link, useLocation } from "react-router-dom";
import { useState, useEffect } from "react";
import { useStateContext } from "../../contexts/AuthProvider";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function Sidebar() {
  const { userRoutes } = useStateContext();
  const location = useLocation();

  const [isOpen, setIsOpen] = useState({});

  // Only sync sub-menu open state based on current path
  useEffect(() => {
    if (!userRoutes) return;

    const newIsOpen = {};

    userRoutes.forEach(route => {
      if (route.children && route.children.length > 0) {
        const match = route.children.some(child => location.pathname.startsWith(child.path));
        if (match) {
          newIsOpen[route.name] = true;
        }
      }
    });

    setIsOpen(newIsOpen);
  }, [userRoutes, location.pathname]);

  const toggleSubMenu = (name) => {
    const newState = {
      ...isOpen,
      [name]: !isOpen[name],
    };
    setIsOpen(newState);
  };

  const isPathActive = (path) => location.pathname === path;

  if (!userRoutes || userRoutes.length === 0) return null;

  const navLinks = [
    // Add Dashboard as the first item
    <li className="nav-group" key="dashboard">
      <div className="nav-group-header">
        <Link to="/dashboard" className={`nav-link ${isPathActive('/dashboard') ? "active" : ""}`}>
          <div className="nav-icon">
            <FontAwesomeIcon icon={solidIconMap.home} />
          </div>
          <span className="nav-text">Dashboard</span>
        </Link>
      </div>
    </li>,
    // ... existing dynamic routes
    ...userRoutes.map((navItem, idx) => {
      if (navItem.side_nav === "true") {
        const hasChildren = Array.isArray(navItem.children) && navItem.children.length > 0;
        const navIcon = solidIconMap[navItem.icon] || solidIconMap.cog;

        if (hasChildren) {
          const childLinks = navItem.children.map((childItem, cidx) => {
            if (childItem.side_nav === "true") {
              const isActive = isPathActive(childItem.path);
              return (
                <li className="nav-item sub-nav-item" key={cidx}>
                  <Link to={childItem.path} className={`nav-link sub-nav-link ${isActive ? "active" : ""}`}>
                    <span className="nav-text">{childItem.name}</span>
                  </Link>
                </li>
              );
            }
            return null;
          });

          return (
            <li className={`nav-group${isOpen[navItem.name] ? " show" : ""}`} key={idx}>
              <div className="nav-group-header">
                <button
                  className="nav-link nav-group-toggle"
                  onClick={() => toggleSubMenu(navItem.name)}>
                  <div className="nav-icon">
                    <FontAwesomeIcon icon={navIcon} />
                  </div>
                  <span className="nav-text">{navItem.name}</span>
                </button>
              </div>
              <ul className="nav-group-items">
                {childLinks}
              </ul>
            </li>
          );
        }

        // No children
        const isActive = isPathActive(navItem.path);
        return (
          <li className="nav-item" key={idx}>
            <Link to={navItem.path} className={`nav-link ${isActive ? "active" : ""}`}>
              <div className="nav-icon">
                <FontAwesomeIcon icon={navIcon} />
              </div>
              <span className="nav-text">{navItem.name}</span>
            </Link>
          </li>
        );
      }
      return null;
    }),
    // Add About page at the end
    <li className="nav-group" key="about">
      <div className="nav-group-header">
        <Link to="/information" className={`nav-link ${isPathActive('/information') ? "active" : ""}`}>
          <div className="nav-icon">
            <FontAwesomeIcon icon={solidIconMap.info} />
          </div>
          <span className="nav-text">About</span>
        </Link>
      </div>
    </li>
  ];

  return (
    <div className="sidebar sidebar-modern" id="sidebar">
      <div className="sidebar-header">
        <div className="sidebar-brand">
          <div className="brand-logo" style={{ 
            backgroundColor: '#6b7280 !important', 
            filter: 'drop-shadow(0 2px 4px rgba(107, 114, 128, 0.3))' 
          }}>
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style={{ color: 'white' }}>
              <rect x="3" y="3" width="7" height="7" rx="1" fill="white" stroke="none"/>
              <rect x="14" y="3" width="7" height="7" rx="1" fill="white" stroke="none"/>
              <rect x="3" y="14" width="7" height="7" rx="1" fill="white" stroke="none"/>
              <rect x="14" y="14" width="7" height="7" rx="1" fill="white" stroke="none"/>
            </svg>
          </div>
          <div className="brand-text">
            <span className="brand-title">INVOICE & PAYMENT</span>
            <span className="brand-subtitle">MANAGEMENT</span>
          </div>
        </div>
      </div>
      <div className="sidebar-content">
        <ul className="sidebar-nav">
          {navLinks}
        </ul>
      </div>
    </div>
  );
}