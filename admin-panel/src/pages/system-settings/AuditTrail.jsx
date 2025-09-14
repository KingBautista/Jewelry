import { useEffect, useRef, useState } from "react";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';
import axiosClient from '../../axios-client';
import DataTable from '../../components/table/DataTable';
import ToastMessage from '../../components/ToastMessage';
import { useAccess } from '../../hooks/useAccess';

export default function AuditTrail() {
  const accessHelper = useAccess();
  const access = accessHelper.hasAccess();

  const [options, setOptions] = useState({
    dataSource: '/system-settings/audit-trail',
    dataFields: {
      created_at: { name: "Date/Time", withSort: true },
      user_name: { 
        name: "User", 
        withSort: true,
        render: (value) => value || 'Unknown'
      },
      module: { name: "Module", withSort: true },
      action: { 
        name: "Action", 
        withSort: true,
        badge: {
          'CREATE': 'text-bg-success',
          'UPDATE': 'text-bg-warning',
          'DELETE': 'text-bg-danger',
          'RESTORE': 'text-bg-info',
          'LOGIN': 'text-bg-success',
          'LOGOUT': 'text-bg-secondary'
        },
        badgeLabels: {
          'CREATE': 'CREATE',
          'UPDATE': 'UPDATE',
          'DELETE': 'DELETE',
          'RESTORE': 'RESTORE',
          'LOGIN': 'LOGIN',
          'LOGOUT': 'LOGOUT'
        }
      },
      description: { name: "Description", withSort: false },
      ip_address: { name: "IP Address", withSort: false },
      user_agent: { name: "User Agent", withSort: false }
    },
    primaryKey: "id",
    redirectUrl: '',
    softDelete: false,
    edit_link: false,
    bulk_action: false,
    hide_actions: true,
  });

  const [params, setParams] = useState({ 
    search: '',
    module: '', 
    action: '', 
    user_id: '', 
    start_date: '', 
    end_date: ''
  });

  const [loading, setLoading] = useState(false);
  const [users, setUsers] = useState([]);
  const [modules, setModules] = useState([]);
  const [actions, setActions] = useState([]);
  const [stats, setStats] = useState(null);
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [collapsedSections, setCollapsedSections] = useState({
    dateRange: false,
    module: false,
    action: false,
    user: false,
    search: false
  });
  const toastAction = useRef();
  const tableRef = useRef();

  // Fetch users for filter
  const fetchUsers = async () => {
    try {
      const response = await axiosClient.get('/options/users');
      setUsers([
        { value: '', label: 'All Users' }, 
        ...(response.data || []).map(user => ({
          value: user.id.toString(),
          label: user.name || user.user_login || user.role_name || 'Unknown User'
        }))
      ]);
      return response;
    } catch (error) {
      console.error('Failed to fetch users:', error);
      setUsers([{ value: '', label: 'All Users' }]);
      throw error;
    }
  };

  // Fetch modules and actions
  const fetchModules = async () => {
    try {
      const response = await axiosClient.get('/system-settings/audit-trail/modules');
      setModules([
        { value: '', label: 'All Modules' }, 
        ...(response.data || []).map(module => ({
          value: module,
          label: module
        }))
      ]);
      return response;
    } catch (error) {
      console.error('Failed to fetch modules:', error);
      setModules([{ value: '', label: 'All Modules' }]);
      throw error;
    }
  };

  const fetchActions = async () => {
    try {
      const response = await axiosClient.get('/system-settings/audit-trail/actions');
      setActions([
        { value: '', label: 'All Actions' }, 
        ...(response.data || []).map(action => ({
          value: action,
          label: action
        }))
      ]);
      return response;
    } catch (error) {
      console.error('Failed to fetch actions:', error);
      setActions([{ value: '', label: 'All Actions' }]);
      throw error;
    }
  };

  // Fetch statistics
  const fetchStats = async () => {
    try {
      const params = { ...params };
      delete params.per_page;
      const response = await axiosClient.get('/system-settings/audit-trail/stats', { params });
      setStats(response.data);
    } catch (error) {
      console.error('Failed to fetch stats:', error);
    }
  };

  useEffect(() => {
    const initializeData = async () => {
      try {
        setLoading(true);
        await Promise.all([
          fetchUsers(),
          fetchModules(),
          fetchActions(),
        ]);
      } catch (error) {
        console.error('Failed to initialize data:', error);
        toastAction.current.showToast('Failed to load some data', 'error');
      } finally {
        setLoading(false);
      }
    };
    
    initializeData();
  }, []);

  // Export handlers
  const exportData = async (format) => {
    try {
      const response = await axiosClient.post('/system-settings/audit-trail/export', {
        format,
        filters: params
      }, {
        responseType: 'blob'
      });

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `audit-trail-${format}-${new Date().toISOString().split('T')[0]}.${format.toLowerCase()}`);
      document.body.appendChild(link);
      link.click();
      link.remove();

      toastAction.current.showToast(`Successfully exported audit trail as ${format.toUpperCase()}`, 'success');
    } catch (error) {
      toastAction.current.showToast(`Failed to export audit trail: ${error.message}`, 'error');
    }
  };

  const handleFilterChange = (key, value) => {
    setParams(prev => ({ ...prev, [key]: value }));
    // Auto-trigger search for non-search fields
    if (key !== 'search') {
      setTimeout(() => handleSearch(), 100);
    }
  };

  const clearFilters = () => {
    setParams({
      search: '',
      module: '', 
      action: '', 
      user_id: '', 
      start_date: '', 
      end_date: ''
    });
    // Close modal after clearing
    setShowFilterModal(false);
  };

  const toggleFilterModal = () => {
    setShowFilterModal(!showFilterModal);
  };

  const toggleSection = (section) => {
    setCollapsedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const handleSearch = () => {
    // Trigger a reload of the DataTable with current params
    if (tableRef.current) {
      tableRef.current.clearPage();
    }
  };

  return (
    <>
      <div className="card mb-0">
        <div className="card-header d-flex justify-content-between align-items-center border-0 py-2">
          <h5 className="mb-2 mt-2">Audit Trail</h5>
          <div className="d-flex gap-1">
            <button className="btn btn-primary btn-sm" onClick={toggleFilterModal}>
              <FontAwesomeIcon icon={solidIconMap.filter} className="me-1" />
              Filters
            </button>
            <button className="btn btn-secondary btn-sm" onClick={clearFilters}>
              <FontAwesomeIcon icon={solidIconMap.xmark} className="me-1" />
              Clear
            </button>
            <button className="btn btn-primary btn-sm me-1" onClick={() => exportData('pdf')}>
              <FontAwesomeIcon icon={solidIconMap.file} className="me-1" />
              Export PDF
            </button>
            <button className="btn btn-primary btn-sm me-1" onClick={() => exportData('csv')}>
              <FontAwesomeIcon icon={solidIconMap.file} className="me-1" />
              Export CSV
            </button>
          </div>
        </div>
        
        <div className="card-body pb-0 pt-1">
          <DataTable options={options} params={params} ref={tableRef} access={access} />
        </div>
      </div>

      {/* Filter Modal */}
      {showFilterModal && (
        <>
          <div className="modal-backdrop fade show" onClick={toggleFilterModal}></div>
          <div className={`modal fade show ${showFilterModal ? 'd-block' : ''}`} style={{ zIndex: 1050 }} onClick={toggleFilterModal}>
            <div className="modal-dialog modal-dialog-centered" style={{ maxWidth: '350px', margin: '0 0 0 auto' }} onClick={(e) => e.stopPropagation()}>
              <div className="modal-content h-100" style={{ height: '100vh', borderRadius: '0', border: 'none', backgroundColor: '#1a1a1a', color: 'white' }}>
                <div className="modal-header border-0" style={{ backgroundColor: '#1a1a1a', color: 'white', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>
                  <h5 className="modal-title" style={{ color: 'white' }}>Filters</h5>
                  <button type="button" className="btn-close btn-close-white" onClick={toggleFilterModal}></button>
                </div>
                <div className="modal-body p-4">
                  <p className="mb-4" style={{ color: '#9ca3af' }}>Refine results using the filters below.</p>
                  
                  {/* Date Range Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('dateRange')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Date Range</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <FontAwesomeIcon 
                          icon={collapsedSections.dateRange ? solidIconMap.chevronDown : solidIconMap.chevronUp} 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.dateRange && (
                      <div className="mt-3">
                        <div className="row g-2">
                          <div className="col-6">
                            <label className="form-label small">Start Date</label>
                            <input 
                              type="date" 
                              className="form-control form-control-sm" 
                              value={params.start_date} 
                              onChange={e => handleFilterChange('start_date', e.target.value)} 
                            />
                          </div>
                          <div className="col-6">
                            <label className="form-label small">End Date</label>
                            <input 
                              type="date" 
                              className="form-control form-control-sm" 
                              value={params.end_date} 
                              onChange={e => handleFilterChange('end_date', e.target.value)} 
                            />
                          </div>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Module Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('module')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Module</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <FontAwesomeIcon 
                          icon={collapsedSections.module ? solidIconMap.chevronDown : solidIconMap.chevronUp} 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.module && (
                      <div className="mt-3">
                        <div className="border rounded p-3" style={{ maxHeight: '200px', overflowY: 'auto', borderColor: '#404040', backgroundColor: 'rgba(0,0,0,0.2)' }}>
                          {modules.map(m => (
                            <div key={m.value} className="form-check">
                              <input 
                                className="form-check-input" 
                                type="radio" 
                                name="module" 
                                id={`module-${m.value}`}
                                value={m.value}
                                checked={params.module === m.value}
                                onChange={e => handleFilterChange('module', e.target.value)}
                              />
                              <label className="form-check-label" htmlFor={`module-${m.value}`} style={{ color: 'white' }}>
                                {m.label}
                              </label>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Action Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('action')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Action</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <FontAwesomeIcon 
                          icon={collapsedSections.action ? solidIconMap.chevronDown : solidIconMap.chevronUp} 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.action && (
                      <div className="mt-3">
                        <div className="border rounded p-3" style={{ maxHeight: '200px', overflowY: 'auto', borderColor: '#404040', backgroundColor: 'rgba(0,0,0,0.2)' }}>
                          {actions.map(a => (
                            <div key={a.value} className="form-check">
                              <input 
                                className="form-check-input" 
                                type="radio" 
                                name="action" 
                                id={`action-${a.value}`}
                                value={a.value}
                                checked={params.action === a.value}
                                onChange={e => handleFilterChange('action', e.target.value)}
                              />
                              <label className="form-check-label" htmlFor={`action-${a.value}`} style={{ color: 'white' }}>
                                {a.label}
                              </label>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* User Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('user')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>User Name</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <FontAwesomeIcon 
                          icon={collapsedSections.user ? solidIconMap.chevronDown : solidIconMap.chevronUp} 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.user && (
                      <div className="mt-3">
                        <div className="border rounded p-3" style={{ maxHeight: '200px', overflowY: 'auto', borderColor: '#404040', backgroundColor: 'rgba(0,0,0,0.2)' }}>
                          {users.map(u => (
                            <div key={u.value} className="form-check">
                              <input 
                                className="form-check-input" 
                                type="radio" 
                                name="user" 
                                id={`user-${u.value}`}
                                value={u.value}
                                checked={params.user_id === u.value || params.user_id === u.value.toString()}
                                onChange={e => handleFilterChange('user_id', e.target.value)}
                              />
                              <label className="form-check-label" htmlFor={`user-${u.value}`} style={{ color: 'white' }}>
                                {u.label}
                              </label>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Search Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('search')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Search</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <FontAwesomeIcon 
                          icon={collapsedSections.search ? solidIconMap.chevronDown : solidIconMap.chevronUp} 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.search && (
                      <div className="mt-3">
                        <div className="mb-3">
                          <label className="form-label" style={{ color: 'white' }}>Search</label>
                          <div className="input-group">
                            <input 
                              type="text" 
                              className="form-control" 
                              placeholder="Search audit trail..."
                              style={{ backgroundColor: '#374151', borderColor: '#4b5563', color: 'white' }}
                              value={params.search}
                              onChange={e => handleFilterChange('search', e.target.value)}
                              onKeyPress={(e) => {
                                if (e.key === 'Enter') {
                                  handleSearch();
                                }
                              }}
                            />
                            <button 
                              className="btn btn-secondary" 
                              type="button"
                              onClick={handleSearch}
                            >
                              <FontAwesomeIcon icon={solidIconMap.magnifyingGlass} />
                            </button>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </>
      )}

      <ToastMessage ref={toastAction} />
    </>
  );
}
