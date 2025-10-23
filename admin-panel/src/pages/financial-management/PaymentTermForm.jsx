import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function PaymentTermForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Payment Term');
  const [paymentTerm, setPaymentTerm] = useState({
    id: null,
    name: '',
    code: '',
    down_payment_percentage: '',
    remaining_percentage: '',
    term_months: '',
    description: '',
    active: true
  });
  const [schedules, setSchedules] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);

  // Load payment term data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/financial-management/payment-terms/${id}`)
        .then(({ data }) => {
          const paymentTermData = data.data || data;
          setPaymentTerm(paymentTermData);
          setSchedules(paymentTermData.schedules || []);
          setIsLoading(false);
          setIsActive(paymentTermData.active);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  }, [id]);

  // Add new schedule
  const addSchedule = () => {
    const termMonths = parseInt(paymentTerm.term_months) || 0;
    const currentSchedules = schedules.length;
    
    if (currentSchedules >= termMonths) {
      toastAction.current.showToast(`Cannot add more months. Maximum allowed is ${termMonths} months based on the term.`, 'warning');
      return;
    }
    
    const newSchedule = {
      month_number: schedules.length + 1,
      percentage: '',
      description: ''
    };
    setSchedules([...schedules, newSchedule]);
  };

  // Remove schedule
  const removeSchedule = (index) => {
    const newSchedules = schedules.filter((_, i) => i !== index);
    // Renumber months
    const renumberedSchedules = newSchedules.map((schedule, i) => ({
      ...schedule,
      month_number: i + 1
    }));
    setSchedules(renumberedSchedules);
  };

  // Generate default schedule with equal monthly payments
  const generateDefaultSchedule = () => {
    const termMonths = parseInt(paymentTerm.term_months) || 1;
    const remainingPercentage = parseFloat(paymentTerm.remaining_percentage) || 0;
    
    if (termMonths <= 0) {
      toastAction.current.showToast('Please enter a valid term months value first', 'warning');
      return;
    }
    
    const equalPercentage = remainingPercentage / termMonths;
    
    const newSchedules = [];
    for (let i = 1; i <= termMonths; i++) {
      newSchedules.push({
        month_number: i,
        percentage: equalPercentage.toFixed(2),
        description: `Month ${i} payment`
      });
    }
    
    setSchedules(newSchedules);
    toastAction.current.showToast(`Generated ${termMonths} equal monthly payments of ${equalPercentage.toFixed(2)}% each`, 'success');
  };

  // Update schedule
  const updateSchedule = (index, field, value) => {
    const newSchedules = [...schedules];
    newSchedules[index] = { ...newSchedules[index], [field]: value };
    setSchedules(newSchedules);
  };

  // Calculate remaining percentage
  const calculateRemainingPercentage = () => {
    const totalSchedulePercentage = schedules.reduce((sum, schedule) => {
      return sum + (parseFloat(schedule.percentage) || 0);
    }, 0);
    return totalSchedulePercentage;
  };

  // Handle form submission
  const onSubmit = (ev) => {
    ev.preventDefault();
    
    // Validate required fields
    if (!paymentTerm.name || !paymentTerm.code || !paymentTerm.down_payment_percentage || !paymentTerm.term_months) {
      toastAction.current.showToast('Please fill in all required fields', 'warning');
      return;
    }

    // Validate percentages
    const downPayment = parseFloat(paymentTerm.down_payment_percentage);
    const remaining = parseFloat(paymentTerm.remaining_percentage);
    const termMonths = parseInt(paymentTerm.term_months);

    if (isNaN(downPayment) || downPayment < 0 || downPayment > 100) {
      toastAction.current.showToast('Down payment percentage must be between 0 and 100', 'warning');
      return;
    }

    if (isNaN(remaining) || remaining < 0 || remaining > 100) {
      toastAction.current.showToast('Remaining percentage must be between 0 and 100', 'warning');
      return;
    }

    if (isNaN(termMonths) || termMonths < 1 || termMonths > 60) {
      toastAction.current.showToast('Term months must be between 1 and 60', 'warning');
      return;
    }

    // Validate schedules
    if (schedules.length > 0) {
      // Validate that number of schedules doesn't exceed term months
      if (schedules.length > termMonths) {
        toastAction.current.showToast(`Number of payment schedules (${schedules.length}) cannot exceed term months (${termMonths})`, 'warning');
        return;
      }

      const totalSchedulePercentage = calculateRemainingPercentage();
      if (Math.abs(totalSchedulePercentage - remaining) > 0.01) {
        toastAction.current.showToast(`Schedule percentages (${totalSchedulePercentage.toFixed(2)}%) must equal remaining percentage (${remaining.toFixed(2)}%)`, 'warning');
        return;
      }

      // Validate individual schedules
      for (let i = 0; i < schedules.length; i++) {
        const schedule = schedules[i];
        if (!schedule.percentage || isNaN(parseFloat(schedule.percentage)) || parseFloat(schedule.percentage) < 0) {
          toastAction.current.showToast(`Please enter valid percentage for month ${schedule.month_number}`, 'warning');
          return;
        }
      }
    }

    setIsLoading(true);

    // Prepare the data for submission
    const submitData = {
      ...paymentTerm,
      down_payment_percentage: downPayment,
      remaining_percentage: remaining,
      term_months: termMonths,
      active: isActive,
      schedules: schedules.map(schedule => ({
        month_number: schedule.month_number,
        percentage: parseFloat(schedule.percentage),
        description: schedule.description || ''
      }))
    };

    const request = paymentTerm.id
      ? axiosClient.put(`/financial-management/payment-terms/${paymentTerm.id}`, submitData)
      : axiosClient.post('/financial-management/payment-terms', submitData);

    request
      .then(() => {
        const action = paymentTerm.id ? 'updated' : 'added';
        toastAction.current.showToast(`Payment term has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/financial-management/payment-terms'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!paymentTerm.id) return;
    
    if (window.confirm('Are you sure you want to delete this payment term?')) {
      setIsLoading(true);
      axiosClient.delete(`/financial-management/payment-terms/${paymentTerm.id}`)
        .then(() => {
          toastAction.current.showToast('Payment term has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/financial-management/payment-terms'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  return (
    <>
    <div className="card">
      <form onSubmit={onSubmit}>
        <div className="card-header">
          <h4>
            {paymentTerm.id ? 'Edit Payment Term' : 'Create New Payment Term'}
          </h4>
          {!paymentTerm.id && <p className="tip-message">Create a new payment term configuration for your jewelry business.</p>}
        </div>
        <div className="card-body">
          {/* Name Field */}
          <Field
            label="Name"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentTerm.name}
                onChange={ev => setPaymentTerm({ ...paymentTerm, name: DOMPurify.sanitize(ev.target.value) })}
                required
                placeholder="e.g., Installment Plan A, Cash Payment"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
            labelStyle={{ color: 'black' }}
          />
          
          {/* Code Field */}
          <Field
            label="Code"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={paymentTerm.code}
                onChange={ev => setPaymentTerm({ ...paymentTerm, code: DOMPurify.sanitize(ev.target.value.toUpperCase()) })}
                required
                placeholder="e.g., INSTALLMENT_A, CASH"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
            labelStyle={{ color: 'black' }}
          />
          
          {/* Payment Breakdown Section */}
          <div className="row mb-3">
            <div className="col-sm-12 col-md-3">
              <label className="form-label" style={{ color: 'black' }}>Payment Breakdown</label>
              <small className="form-text d-block" style={{ color: '#6c757d', opacity: 0.8 }}>Define the payment structure</small>
            </div>
            <div className="col-sm-12 col-md-9">
              <div className="border rounded p-3">
                <div className="row">
                  <div className="col-md-6 mb-3">
                    <label className="form-label" style={{ color: 'black' }}>Down Payment (%)</label>
                    <div className="input-group">
                      <input
                        className="form-control"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={paymentTerm.down_payment_percentage}
                        onChange={ev => {
                          const dp = parseFloat(ev.target.value) || 0;
                          const remaining = 100 - dp;
                          setPaymentTerm({ 
                            ...paymentTerm, 
                            down_payment_percentage: ev.target.value,
                            remaining_percentage: remaining.toFixed(2)
                          });
                        }}
                        required
                        placeholder="e.g., 30.00"
                      />
                      <span className="input-group-text">%</span>
                    </div>
                    <small className="form-text" style={{ color: '#6c757d', opacity: 0.8 }}>Initial payment amount</small>
                  </div>
                  <div className="col-md-6 mb-3">
                    <label className="form-label" style={{ color: 'black' }}>Remaining (%)</label>
                    <div className="input-group">
                      <input
                        className="form-control bg-body-secondary"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={paymentTerm.remaining_percentage}
                        onChange={ev => setPaymentTerm({ ...paymentTerm, remaining_percentage: ev.target.value })}
                        required
                        placeholder="e.g., 70.00"
                        readOnly
                        style={{ flex: 1 }}
                      />
                      <span className="input-group-text">%</span>
                    </div>
                    <small className="form-text" style={{ color: '#6c757d', opacity: 0.8 }}>Auto-calculated from down payment</small>
                  </div>
                </div>
                <div className="row">
                  <div className="col-12">
                    <div className="alert alert-dark mb-0" style={{ backgroundColor: '#343a40', color: 'white' }}>
                      <strong>Breakdown Summary:</strong> 
                      Down Payment: {paymentTerm.down_payment_percentage || 0}% | 
                      Remaining: {paymentTerm.remaining_percentage || 0}% | 
                      Total: {(parseFloat(paymentTerm.down_payment_percentage || 0) + parseFloat(paymentTerm.remaining_percentage || 0)).toFixed(2)}%
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          {/* Term Months Field */}
          <Field
            label="Term (Months)"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="number"
                min="1"
                max="60"
                value={paymentTerm.term_months}
                onChange={ev => setPaymentTerm({ ...paymentTerm, term_months: ev.target.value })}
                required
                placeholder="e.g., 5"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
            labelStyle={{ color: 'black' }}
          />
          
          {/* Description Field */}
          <Field
            label="Description"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={paymentTerm.description}
                onChange={ev => setPaymentTerm({ ...paymentTerm, description: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Optional description for this payment term"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
            labelStyle={{ color: 'black' }}
          />

          {/* Payment Schedules */}
          <div className="row mb-3">
            <div className="col-sm-12 col-md-3">
              <label className="form-label" style={{ color: 'black' }}>Payment Schedule</label>
              <small className="form-text d-block" style={{ color: '#6c757d', opacity: 0.8 }}>Define monthly payment breakdown</small>
            </div>
            <div className="col-sm-12 col-md-9">
              <div className="border rounded p-3">
                <div className="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <h6 className="mb-0">Monthly Payment Breakdown</h6>
                    <small style={{ color: '#6c757d', opacity: 0.8 }}>Term: {paymentTerm.term_months || 0} months | Remaining: {paymentTerm.remaining_percentage || 0}%</small>
                  </div>
                  <div>
                    <button 
                      type="button" 
                      className="btn btn-sm btn-outline-primary me-2"
                      onClick={addSchedule}
                      disabled={schedules.length >= (parseInt(paymentTerm.term_months) || 0)}
                      title={schedules.length >= (parseInt(paymentTerm.term_months) || 0) ? `Maximum ${paymentTerm.term_months || 0} months allowed` : 'Add a new month to the schedule'}
                    >
                      <FontAwesomeIcon icon={solidIconMap.plus} className="me-1" />
                      Add Month
                    </button>
                    <button 
                      type="button" 
                      className="btn btn-sm btn-outline-secondary"
                      onClick={generateDefaultSchedule}
                      disabled={!paymentTerm.term_months || parseInt(paymentTerm.term_months) <= 0}
                      title={!paymentTerm.term_months || parseInt(paymentTerm.term_months) <= 0 ? 'Please enter valid term months first' : 'Generate equal monthly payments'}
                    >
                      <FontAwesomeIcon icon={solidIconMap.magic} className="me-1" />
                      Auto Generate
                    </button>
                  </div>
                </div>
                
                {schedules.length === 0 ? (
                  <div className="text-center py-4">
                    <p className="mb-3" style={{ color: '#6c757d', opacity: 0.8 }}>No payment schedules defined.</p>
                    <p style={{ color: '#6c757d', opacity: 0.8 }}>Click "Add Month" to create monthly payment breakdowns or "Auto Generate" to create equal monthly payments.</p>
                  </div>
                ) : (
                  <>
                    <div className="table-responsive" style={{ height: 'auto', maxHeight: 'none', overflow: 'visible' }}>
                      <table className="table table-sm table-hover" style={{ whiteSpace: 'nowrap' }}>
                        <thead className="table-light">
                          <tr>
                            <th width="15%">Month</th>
                            <th width="20%">Percentage</th>
                            <th width="45%">Description</th>
                            <th width="20%">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {schedules.map((schedule, index) => (
                            <tr key={index}>
                              <td>
                                <div className="d-flex align-items-center">
                                  <span className="badge bg-primary me-2">{schedule.month_number}</span>
                                  <span style={{ color: '#F7E7CE', opacity: 0.8 }}>&nbsp;Month {schedule.month_number}</span>
                                </div>
                              </td>
                              <td>
                                <div className="input-group input-group-sm">
                                  <input
                                    className="form-control"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={schedule.percentage}
                                    onChange={ev => updateSchedule(index, 'percentage', ev.target.value)}
                                    placeholder="0.00"
                                  />
                                  <span className="input-group-text">%</span>
                                </div>
                              </td>
                              <td>
                                <input
                                  className="form-control form-control-sm"
                                  type="text"
                                  value={schedule.description}
                                  onChange={ev => updateSchedule(index, 'description', DOMPurify.sanitize(ev.target.value))}
                                  placeholder="e.g., First installment, Second payment..."
                                />
                              </td>
                              <td>
                                <button
                                  type="button"
                                  className="btn btn-sm btn-outline-danger"
                                  onClick={() => removeSchedule(index)}
                                  title="Remove this month"
                                >
                                  <FontAwesomeIcon icon={solidIconMap.trash} />
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                    
                    {/* Schedule Summary */}
                    <div className="mt-2 p-3 bg-body-secondary rounded" style={{ backgroundColor: '#343a40', color: 'white' }}>
                      <div className="row">
                        <div className="col-md-6">
                          <strong style={{ color: 'white' }}>Schedule Summary:</strong>
                          <ul className="list-unstyled mt-2 mb-0" style={{ color: 'white' }}>
                            <li>Total Months: <span className={schedules.length > (parseInt(paymentTerm.term_months) || 0) ? 'text-danger' : 'text-success'}>{schedules.length}</span> / {paymentTerm.term_months || 0}</li>
                            <li>Total Percentage: <span className={Math.abs(calculateRemainingPercentage() - (parseFloat(paymentTerm.remaining_percentage) || 0)) > 0.01 ? 'text-danger' : 'text-success'}>{calculateRemainingPercentage().toFixed(2)}%</span></li>
                            <li>Remaining Target: {paymentTerm.remaining_percentage || 0}%</li>
                          </ul>
                        </div>
                        <div className="col-md-6">
                          <strong style={{ color: 'white' }}>Validation:</strong>
                          <div className="mt-2">
                            {schedules.length > (parseInt(paymentTerm.term_months) || 0) ? (
                              <span className="text-danger">
                                <FontAwesomeIcon icon={solidIconMap.exclamation} className="me-1" />
                                Too many months! Maximum allowed is {paymentTerm.term_months || 0}
                              </span>
                            ) : Math.abs(calculateRemainingPercentage() - (parseFloat(paymentTerm.remaining_percentage) || 0)) <= 0.01 ? (
                              <span className="text-success">
                                <FontAwesomeIcon icon={solidIconMap.check} className="me-1" />
                                Schedule percentages match remaining amount
                              </span>
                            ) : (
                              <span className="text-danger">
                                <FontAwesomeIcon icon={solidIconMap.exclamation} className="me-1" />
                                Schedule percentages don't match remaining amount
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
          
          {/* Active Field */}
          <Field
            label="Active"
            inputComponent={
              <input
                className="form-check-input"
                type="checkbox"
                checked={isActive}
                onChange={() => setIsActive(!isActive)}
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
            labelStyle={{ color: 'black' }}
          />
        </div>
        <div className="card-footer">
          <div className="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            <div className="d-flex flex-column flex-sm-row gap-2">
              <Link type="button" to="/financial-management/payment-terms" className="btn btn-secondary w-100 w-sm-auto">
                <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
                Cancel
              </Link>
              <button type="submit" className="btn btn-secondary w-100 w-sm-auto">
                <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
                {buttonText} &nbsp;
                {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
              </button>
            </div>
            {paymentTerm.id && (
              <button 
                type="button" 
                className="btn btn-danger w-100 w-sm-auto" 
                onClick={handleDelete}
                disabled={isLoading}
              >
                <FontAwesomeIcon icon={solidIconMap.trash} className="me-2" />
                Delete
              </button>
            )}
          </div>
        </div>
      </form>
    </div>
    <ToastMessage ref={toastAction} />
    </>
  );
}
