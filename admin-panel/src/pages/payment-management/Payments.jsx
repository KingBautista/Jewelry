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

export default function Payments() {

  const accessHelper = useAccess();
  const access = accessHelper.hasAccess(); // defaults to window.location.pathname
  // Grouping states that are related
  const [dataStatus, setDataStatus] = useState({
    totalRows: 0,
    totalTrash: 0,
    classAll: 'current',
    classTrash: null,
  });

  const [options, setOptions] = useState({
    dataSource: '/payment-management/payments',
    dataFields: {
      reference_number: { name: "Reference #", withSort: true },
      customer_name: { name: "Customer", withSort: true },
      payment_type: { name: "Payment Type", withSort: true },
      formatted_amount_paid: { name: "Amount Paid", withSort: true },
      formatted_expected_amount: { name: "Expected Amount", withSort: true },
      payment_method_name: { name: "Payment Method", withSort: true },
      status: {
        name: "Status",
        withSort: true,
        badge: {
          'pending': 'bg-warning',
          'approved': 'bg-info',
          'confirmed': 'bg-success',
          'rejected': 'bg-danger'
        },
        badgeLabels: {
          'pending': 'Pending',
          'approved': 'Approved',
          'confirmed': 'Confirmed',
          'rejected': 'Rejected'
        }
      },
      payment_date: { name: "Payment Date", withSort: true },
      confirmed_at: { name: "Confirmed At", withSort: true },
      created_at: { name: "Created At", withSort: true },
    },
    softDelete: true,
    primaryKey: "id",
    redirectUrl: '',
    edit_link: true,
    bulk_action: false,
  });

  const [params, setParams] = useState({ search: '' });
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [collapsedSections, setCollapsedSections] = useState({
    status: false,
    payment_type: false,
    search: false,
  });

  // Refs
  const searchRef = useRef();
  const tableRef = useRef();
  const modalAction = useRef();
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
      dataSource: isTrash ? '/payment-management/archived/payments' : '/payment-management/payments',
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
      payment_type: '',
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
          <h4>Payments</h4>
        </div>
        <div className="px-4" style={{ paddingTop: '0.50rem' }}>
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
                <Link to="/payment-management/payments/create" className="btn btn-secondary" type="button">
                  <FontAwesomeIcon icon={solidIconMap.plus} className="me-2" />
                  Create New Payment
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
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Payment Status</h6>
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
                              id="status-pending"
                              value="pending"
                              checked={params.status === 'pending'}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-pending" style={{ color: 'white' }}>
                              Pending
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="status" 
                              id="status-approved"
                              value="approved"
                              checked={params.status === 'approved'}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-approved" style={{ color: 'white' }}>
                              Approved
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="status" 
                              id="status-confirmed"
                              value="confirmed"
                              checked={params.status === 'confirmed'}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-confirmed" style={{ color: 'white' }}>
                              Confirmed
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="status" 
                              id="status-rejected"
                              value="rejected"
                              checked={params.status === 'rejected'}
                              onChange={e => handleFilterChange('status', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="status-rejected" style={{ color: 'white' }}>
                              Rejected
                            </label>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Payment Type Filter */}
                  <div className="mb-4">
                    <div 
                      className="d-flex justify-content-between align-items-center cursor-pointer" 
                      onClick={() => toggleSection('payment_type')}
                      style={{ cursor: 'pointer' }}>
                      <h6 className="fw-bold mb-0" style={{ color: '#3b82f6' }}>Payment Type</h6>
                      <span style={{ color: '#9ca3af' }}>
                        <img 
                          src={collapsedSections.payment_type ? "/assets/new-icons/icons-bold/fi-br-angle-small-down.svg" : "/assets/new-icons/icons-bold/fi-br-angle-small-up.svg"} 
                          alt="Toggle" 
                          style={{ width: '12px', height: '12px' }} 
                        />
                      </span>
                    </div>
                    {!collapsedSections.payment_type && (
                      <div className="mt-3">
                        <div className="border rounded p-3" style={{ borderColor: '#404040', backgroundColor: 'rgba(0,0,0,0.2)' }}>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="payment_type" 
                              id="payment-type-all"
                              value=""
                              checked={params.payment_type === ''}
                              onChange={e => handleFilterChange('payment_type', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="payment-type-all" style={{ color: 'white' }}>
                              All Payment Types
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="payment_type" 
                              id="payment-type-downpayment"
                              value="downpayment"
                              checked={params.payment_type === 'downpayment'}
                              onChange={e => handleFilterChange('payment_type', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="payment-type-downpayment" style={{ color: 'white' }}>
                              Down Payment
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="payment_type" 
                              id="payment-type-monthly"
                              value="monthly"
                              checked={params.payment_type === 'monthly'}
                              onChange={e => handleFilterChange('payment_type', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="payment-type-monthly" style={{ color: 'white' }}>
                              Monthly Payment
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="payment_type" 
                              id="payment-type-full"
                              value="full"
                              checked={params.payment_type === 'full'}
                              onChange={e => handleFilterChange('payment_type', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="payment-type-full" style={{ color: 'white' }}>
                              Full Payment
                            </label>
                          </div>
                          <div className="form-check">
                            <input 
                              className="form-check-input" 
                              type="radio" 
                              name="payment_type" 
                              id="payment-type-partial"
                              value="partial"
                              checked={params.payment_type === 'partial'}
                              onChange={e => handleFilterChange('payment_type', e.target.value)}
                            />
                            <label className="form-check-label" htmlFor="payment-type-partial" style={{ color: 'white' }}>
                              Partial Payment
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
                              placeholder="Search payments..."
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

      <NotificationModal params={modalParams} ref={modalAction} />
      <ToastMessage ref={toastAction} />
    </>
  );
}
