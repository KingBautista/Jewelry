import { useEffect, useRef, useState } from "react"
import { Link } from "react-router-dom";
import axiosClient from "../../axios-client";
import DataTable from "../../components/table/DataTable";
import NotificationModal from "../../components/NotificationModal";
import ToastMessage from "../../components/ToastMessage";
import SearchBox from "../../components/SearchBox";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';
import { useAccess } from '../../hooks/useAccess';

export default function PaymentMethods() {

  const accessHelper = useAccess();
  const access = accessHelper.hasAccess();
  
  // Grouping states that are related
  const [dataStatus, setDataStatus] = useState({
    totalRows: 0,
    totalTrash: 0,
    classAll: 'current',
    classTrash: null,
  });

  const [options, setOptions] = useState({
    dataSource: '/financial-config/payment-methods',
    dataFields: {
      'bank_name': { name: 'Bank Name', withSort: true },
      'account_name': { name: 'Account Name', withSort: true },
      'account_number': { name: 'Account Number', withSort: false },
      'description': { name: 'Description', withSort: false },
      'status': {
        name: "Status",
        withSort: true,
        badge: {
          'Active': 'bg-success',
          'Inactive': 'bg-warning text-dark'
        },
        badgeLabels: {
          'Active': 'Active',
          'Inactive': 'Inactive'
        }
      },
      'created_at': { name: 'Created', withSort: true },
    },
    softDelete: true,
    primaryKey: 'id',
    redirectUrl: '',
    edit_link: true,
    bulk_action: false,
  });

  const [params, setParams] = useState({ search: '' });
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [collapsedSections, setCollapsedSections] = useState({
    status: false,
    search: false,
  });

  // Refs
  const searchRef = useRef();
  const tableRef = useRef();
  const modalAction = useRef();
  const bulkAction = useRef();
  const toastAction = useRef();

  const [modalParams, setModalParams] = useState({
    id: 'businessModal',
    title: "Confirmation",
    descriptions: "Are you sure to apply these changes?",
  });

  // Helper function to update data source and tabs
  const handleTabChange = (ev, type) => {
    ev.preventDefault();

    const isTrash = type === 'Trash';
    setDataStatus(prevStatus => ({
      ...prevStatus,
      classAll: isTrash ? null : 'current',
      classTrash: isTrash ? 'current' : null,
    }));

    // Clear search input and parameters
    searchRef.current.value = '';
    setParams({ search: '' });
    tableRef.current.clearPage();

    setOptions(prevOptions => ({
      ...prevOptions,
      dataSource: isTrash ? '/financial-config/archived/payment-methods' : '/financial-config/payment-methods',
    }));
  };

  // Handle search action
  const handleSearch = () => {
    const searchValue = searchRef.current.value;
    setParams(prevParams => ({
      ...prevParams,
      search: searchValue,
    }));
  };

  const handleFilterChange = (key, value) => {
    setParams(prev => ({ ...prev, [key]: value }));
    // Auto-trigger search for non-search fields
    if (key !== 'search') {
      setTimeout(() => {
        // Clear search input when applying other filters
        if (searchRef.current) {
          searchRef.current.value = '';
        }
        handleSearch();
      }, 100);
    }
  };

  // Sync search input with params
  const syncSearchInput = () => {
    if (searchRef.current && searchRef.current.value !== params.search) {
      searchRef.current.value = params.search || '';
    }
  };

  // Effect to sync search input when params change
  useEffect(() => {
    syncSearchInput();
  }, [params.search]);

  const clearFilters = () => {
    setParams({
      search: '',
      status: '',
    });
    // Clear search input
    if (searchRef.current) {
      searchRef.current.value = '';
    }
    // Close modal after clearing
    setShowFilterModal(false);
    // Trigger reload to show all data
    setTimeout(() => handleSearch(), 100);
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

  // Show modal and set description based on action
  const showNotificationModal = () => {
    const action = bulkAction.current.value;
    if (!action) {
      toastAction.current.showToast('Please select an action first', 'warning');
      return;
    }

    const isDelete = action === 'delete' && dataStatus.classTrash;
    const message = isDelete 
      ? 'You are about to permanently delete these items from your site. This action cannot be undone.' 
      : 'Are you sure to apply this change?';
    
    setModalParams((prev) => ({
      ...prev,
      descriptions: message,
    }));
    modalAction.current.show();
  };

  // Handle bulk actions (restore, delete)
  const onConfirm = () => {
    const selectedRows = tableRef.current.getSelectedRows();
    const action = bulkAction.current.value;

    if (!action) {
      toastAction.current.showToast('Please select an action first', 'warning');
      return;
    }

    if (selectedRows.length === 0) {
      toastAction.current.showToast('Please select at least one item', 'warning');
      return;
    }

    const url = getBulkActionUrl(action, dataStatus.classTrash);
    const payload = { ids: selectedRows };

    axiosClient.post(url, payload)
    .then(({ data }) => {
      handleActionResponse(action, data);
    }).catch((errors) => {
      toastAction.current.showError(errors.response);
    });
  };

  // Helper to get URL based on action and trash state
  const getBulkActionUrl = (action, isTrash) => {
    switch (action) {
      case 'restore':
        return '/financial-config/payment-methods/bulk/restore';
      case 'delete':
        return isTrash ? '/financial-config/payment-methods/bulk/force-delete' : '/financial-config/payment-methods/bulk/delete';
      default:
        return '';
    }
  };

  // Handle API response after bulk action
  const handleActionResponse = (action, data) => {
    const toastType = action === 'restore' ? 'success' : 'danger';
    toastAction.current.showToast(data.message, toastType);
    modalAction.current.hide();
    tableRef.current.reload();
    bulkAction.current.value = '';
  };

  // Show total rows and total trash count
  const showSubSub = (all, archived) => {
    setDataStatus(prevStatus => ({
      ...prevStatus,
      totalRows: all,
      totalTrash: archived,
    }));
  };

  return (
    <>
      <div className="card mb-2">
        <div className="card-header d-flex justify-content-between align-items-center border-0">
          <h4>Payment Methods</h4>
        </div>
        <div className="card-header pb-0 pt-0 border-0">
          <div className="row"> 
            <div className="col-md-7 col-12">
               <div className="d-flex align-items-center gap-2">
                 <SearchBox ref={searchRef} onClick={handleSearch} />
                 <button className="btn btn-secondary h-100 text-nowrap" onClick={toggleFilterModal}>
                   <img src="/assets/new-icons/icons-bold/fi-br-filter.svg" alt="Filter" className="me-1" style={{ width: '14px', height: '14px', filter: 'brightness(0) invert(1)' }} />
                   Filters
                 </button>
                 <button className="btn btn-secondary h-100 text-nowrap" onClick={clearFilters}>
                   <img src="/assets/new-icons/icons-bold/fi-br-cross.svg" alt="Clear" className="me-1" style={{ width: '14px', height: '14px', filter: 'brightness(0) invert(1)' }} />
                   Clear
                 </button>
               </div>
             </div>
             <div className="col-md-5 col-12 d-flex justify-content-end align-items-center">
              {access?.can_create && 
                <Link to="/financial-management/payment-methods/create" className="btn btn-secondary" type="button">
                  <FontAwesomeIcon icon={solidIconMap.plus} className="me-2" />
                  Create New Payment Method
                </Link>
              }
            </div>
          </div>
        </div>
        <div className="card-body pb-0 pt-3">
          <DataTable options={options} params={params} ref={tableRef} setSubSub={showSubSub} access={access} />
        </div>
      </div>

      {/* Filter Modal */}
      {showFilterModal && (
        <>
          <div className="modal-backdrop fade show" onClick={toggleFilterModal}></div>
          <div className={`modal fade show ${showFilterModal ? 'd-block' : ''}`} style={{ zIndex: 1050 }} onClick={toggleFilterModal}>
            <div className="modal-dialog modal-dialog-centered" style={{ maxWidth: '350px', margin: '0 0 0 auto', height: '100vh' }} onClick={(e) => e.stopPropagation()}>
              <div className="modal-content h-100" style={{ height: '100vh', borderRadius: '0', border: 'none', backgroundColor: '#1a1a1a', color: 'white' }}>
                <div className="modal-header border-0" style={{ backgroundColor: '#1a1a1a', color: 'white', borderBottom: '1px solid rgba(255,255,255,0.1)' }}>
                  <h5 className="modal-title" style={{ color: 'white' }}>Filters</h5>
                  <button type="button" className="btn-close btn-close-white" onClick={toggleFilterModal}></button>
                </div>
                <div className="modal-body p-4">
                  <p className="mb-4" style={{ color: '#9ca3af' }}>Refine results using the filters below.</p>
                  
                  {/* Status Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('status')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Status</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <img 
                          src={collapsedSections.status ? "/assets/new-icons/icons-bold/fi-br-angle-small-down.svg" : "/assets/new-icons/icons-bold/fi-br-angle-small-up.svg"} 
                          alt="Toggle" 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.status && (
                      <div className="mt-3">
                        <div className="border rounded p-3" style={{ borderColor: '#404040', backgroundColor: 'rgba(0,0,0,0.2)' }}>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="status" 
                              id="status-all"
                              value=""
                              checked={params.status === ''}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-all" style={{ color: 'white' }}>
                              All Status
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="status" 
                              id="status-active"
                              value="Active"
                              checked={params.status === 'Active'}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-active" style={{ color: 'white' }}>
                              Active
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="status" 
                              id="status-inactive"
                              value="Inactive"
                              checked={params.status === 'Inactive'}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-inactive" style={{ color: 'white' }}>
                              Inactive
                            </label>
                          </div>
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
                        <img 
                          src={collapsedSections.search ? "/assets/new-icons/icons-bold/fi-br-angle-small-down.svg" : "/assets/new-icons/icons-bold/fi-br-angle-small-up.svg"} 
                          alt="Toggle" 
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
                              placeholder="Search payment methods..."
                              style={{ backgroundColor: '#374151', borderColor: '#4b5563', color: 'white' }}
                              value={params.search || ''}
                              onChange={e => {
                                handleFilterChange('search', e.target.value);
                                // Update the main search box as well
                                if (searchRef.current) {
                                  searchRef.current.value = e.target.value;
                                }
                              }}
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
                              <img 
                                src="/assets/new-icons/icons-bold/fi-br-search.svg" 
                                alt="Search" 
                                style={{ width: '14px', height: '14px', filter: 'brightness(0) invert(1)' }} 
                              />
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

      <NotificationModal params={modalParams} ref={modalAction} confirmEvent={onConfirm} />
      <ToastMessage ref={toastAction} />
    </>
  );
}
