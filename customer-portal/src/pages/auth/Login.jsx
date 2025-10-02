import { Link, useNavigate } from 'react-router-dom';
import { useStateContext } from '../../contexts/AuthProvider';
import { useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';
import DOMPurify from 'dompurify';

export default function Login() {
  const emailRef = useRef();
  const passwordRef = useRef();
  const navigate = useNavigate();
  
  const [isValidated, setValidated] = useState();
  const [message, setMessage] = useState();
  const [errors, setErrors] = useState();
  const [isLoading, setIsLoading] = useState();
  const [showPassword, setShowPassword] = useState(false);

  const { login } = useStateContext();

  const onSubmit = (ev) => {
    ev.preventDefault();
    setIsLoading(true);

    const payload = {
      email: emailRef.current.value,
      password: passwordRef.current.value,
    };

    login(payload.email, payload.password)
      .then(() => {
        navigate('/dashboard');
      })
      .catch((error) => {
        const response = error.response;
        if (response && response.status === 422) {
          setErrors(response.data.errors);
          setValidated('needs-validation was-validated');
        } else {
          setErrors({ general: ['Login failed. Please try again.'] });
        }
        setIsLoading(false);
      });
  };

  // Sanitize the message and errors
  const sanitizedMessage = message ? DOMPurify.sanitize(message) : '';
  const sanitizedErrors = errors ? Object.keys(errors).reduce((acc, key) => {
    acc[key] = DOMPurify.sanitize(errors[key]);
    return acc;
  }, {}) : {};

  return (
    <div className="min-vh-100 d-flex align-items-center justify-content-center" style={{ background: 'var(--gradient-light)' }}>
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-8">
            <div className="row g-0 rounded-4 overflow-hidden shadow-xl" style={{ 
              backgroundColor: 'var(--surface-color)', 
              border: '1px solid var(--border-color)',
              boxShadow: '0 10px 25px rgba(0,0,0,0.1)'
            }}>
              {/* Login Form - Left Side */}
              <div className="col-lg-6">
                <div className="h-100 d-flex flex-column justify-content-center">
                  <div className="p-5">
                    {/* Header Section */}
                    <div className="text-center mb-5">
                      <FontAwesomeIcon icon={solidIconMap.gem} className="fs-1 mb-4" style={{ 
                        filter: 'drop-shadow(0 4px 8px rgba(0,0,0,0.3))',
                        color: 'var(--champagne-primary)'
                      }} />
                      <h1 className="fw-bold mb-2" style={{ 
                        fontSize: '2.5rem', 
                        letterSpacing: '-0.5px',
                        color: 'var(--text-color)'
                      }}>Welcome Back</h1>
                      <p className="mb-0 fs-5" style={{ 
                        opacity: 0.8,
                        color: 'var(--text-color-muted)'
                      }}>Sign in to your customer account</p>
                    </div>

                    <form onSubmit={onSubmit} className={isValidated}>
                      {/* Success Message */}
                      {sanitizedMessage && 
                        <div className="alert alert-success border-0 shadow-sm" role="alert">
                          <div className="d-flex align-items-center">
                            <FontAwesomeIcon icon={solidIconMap.check} className="me-2" />
                            <p className="mb-0 fw-medium">{sanitizedMessage}</p>
                          </div>
                        </div>
                      }
                      
                      {/* Error Messages */}
                      {Object.keys(sanitizedErrors).length > 0 && 
                        <div className="alert alert-danger border-0 shadow-sm" role="alert">
                          <div className="d-flex align-items-center mb-2">
                            <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" />
                            <span className="fw-medium">Please fix the following errors:</span>
                          </div>
                          {Object.keys(sanitizedErrors).map(key => (
                            <div key={key} className="ms-4">• {sanitizedErrors[key]}</div>
                          ))}
                        </div>
                      }
                      
                      {/* Email Input */}
                      <div className="mb-4">
                        <label htmlFor="email" className="form-label fw-semibold mb-2" style={{ 
                          fontSize: '1.1rem',
                          color: 'var(--text-color)'
                        }}>Email Address</label>
                        <div className="input-group input-group-lg shadow-sm">
                          <span className="input-group-text border-end-0 border-2" style={{ 
                            borderColor: 'var(--border-color)', 
                            color: 'var(--text-color-muted)',
                            backgroundColor: 'var(--surface-color-secondary)'
                          }}>
                            <FontAwesomeIcon icon={solidIconMap.envelope} />
                          </span>
                          <input 
                            id="email"
                            ref={emailRef} 
                            className="form-control border-start-0 border-2" 
                            type="email" 
                            placeholder="Enter your email" 
                            required
                            style={{ 
                              fontSize: '1rem', 
                              borderColor: 'var(--border-color)',
                              backgroundColor: 'var(--surface-color)',
                              color: 'var(--text-color)'
                            }}
                          />
                        </div>
                      </div>
                      
                      {/* Password Input */}
                      <div className="mb-4">
                        <label htmlFor="password" className="form-label fw-semibold mb-2" style={{ 
                          fontSize: '1.1rem',
                          color: 'var(--text-color)'
                        }}>Password</label>
                        <div className="input-group input-group-lg shadow-sm">
                          <span className="input-group-text border-end-0 border-2" style={{ 
                            borderColor: 'var(--border-color)', 
                            color: 'var(--text-color-muted)',
                            backgroundColor: 'var(--surface-color-secondary)'
                          }}>
                            <FontAwesomeIcon icon={solidIconMap.lock} />
                          </span>
                          <input
                            id="password"
                            ref={passwordRef}
                            className="form-control border-start-0 border-2"
                            type={showPassword ? "text" : "password"}
                            placeholder="Enter your password"
                            required
                            style={{ 
                              fontSize: '1rem', 
                              borderColor: 'var(--border-color)',
                              backgroundColor: 'var(--surface-color)',
                              color: 'var(--text-color)'
                            }}
                          />
                        </div>
                      </div>
                      
                      {/* Show Password Toggle */}
                      <div className="mb-4">
                        <div className="form-check">
                          <input
                            type="checkbox"
                            id="showPassword"
                            checked={showPassword}
                            onChange={() => setShowPassword(!showPassword)}
                            className="form-check-input"
                            style={{ 
                              width: '1.3em', 
                              height: '1.3em',
                              accentColor: 'var(--champagne-primary)'
                            }}
                          />
                          <label htmlFor="showPassword" className="form-check-label fw-semibold" style={{ 
                            fontSize: '1rem',
                            color: 'var(--text-color)'
                          }}>
                            Show password
                          </label>
                        </div>
                      </div>
                      
                      {/* Submit Button */}
                      <div className="mb-4">
                        <button 
                          className="btn btn-primary w-100 fw-bold" 
                          type="submit"
                          disabled={isLoading}
                          style={{ 
                            padding: '0.8rem 1.5rem',
                            fontSize: '1.1rem',
                            borderRadius: '0.75rem'
                          }}
                        >
                          {isLoading ? (
                            <>
                              <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                              Signing In...
                            </>
                          ) : (
                            <>
                              <FontAwesomeIcon icon={solidIconMap.signIn} className="me-2" />
                              Sign In
                            </>
                          )}
                        </button>
                      </div>
                      
                      {/* Forgot Password Link */}
                      <div className="text-center">
                        <Link to="/forgot-password" className="text-decoration-none fw-semibold" style={{ 
                          fontSize: '1rem', 
                          transition: 'all 0.3s ease',
                          color: 'var(--text-color)'
                        }}>
                          Forgot your password?
                        </Link>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              
              {/* Content Section - Right Side */}
              <div className="col-lg-6 position-relative">
                <div 
                  className="h-100 d-flex align-items-center justify-content-center"
                  style={{ 
                    background: 'var(--gradient-champagne)',
                    minHeight: '500px'
                  }}
                >
                  {/* Background Pattern */}
                  <div 
                    className="position-absolute w-100 h-100"
                    style={{
                      backgroundImage: 'radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 0%, transparent 50%), linear-gradient(45deg, rgba(255,255,255,0.05) 0%, transparent 50%)',
                      backgroundSize: '300px 300px, 300px 300px, 100px 100px'
                    }}
                  ></div>
                  
                  {/* Content */}
                  <div className="text-center position-relative">
                    {/* Icon */}
                    <div className="mb-5">
                      <FontAwesomeIcon icon={solidIconMap.gem} style={{ 
                        fontSize: '4rem', 
                        filter: 'drop-shadow(0 8px 16px rgba(0,0,0,0.3))',
                        color: 'var(--text-color)'
                      }} />
                    </div>
                    
                    {/* Welcome Text */}
                    <h2 className="fw-bold mb-4" style={{ 
                      fontSize: '2.8rem', 
                      letterSpacing: '-1px', 
                      textShadow: '0 4px 8px rgba(0,0,0,0.3)',
                      color: 'var(--text-color)'
                    }}>
                      Customer Portal
                    </h2>
                    
                    <p className="fs-4" style={{ 
                      maxWidth: '450px', 
                      margin: '0 auto', 
                      opacity: 0.95, 
                      lineHeight: '1.6',
                      color: 'var(--text-color-muted)'
                    }}>
                      Access your invoices, track payments, and manage your account
                    </p>
                    
                    {/* Features List */}
                    <div className="row g-4 text-start" style={{ maxWidth: '380px', margin: '0 auto' }}>
                      <div className="col-12">
                        <div className="d-flex align-items-center p-3 rounded" style={{ 
                          backgroundColor: 'rgba(255,255,255,0.2)', 
                          backdropFilter: 'blur(10px)', 
                          border: '1px solid rgba(255,255,255,0.3)' 
                        }}>
                          <FontAwesomeIcon icon={solidIconMap.check} className="me-3" style={{ 
                            fontSize: '1.3rem',
                            color: 'var(--text-color)'
                          }} />
                          <span className="fw-medium" style={{ color: 'var(--text-color)' }}>View your invoices</span>
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="d-flex align-items-center p-3 rounded" style={{ 
                          backgroundColor: 'rgba(255,255,255,0.2)', 
                          backdropFilter: 'blur(10px)', 
                          border: '1px solid rgba(255,255,255,0.3)' 
                        }}>
                          <FontAwesomeIcon icon={solidIconMap.check} className="me-3" style={{ 
                            fontSize: '1.3rem',
                            color: 'var(--text-color)'
                          }} />
                          <span className="fw-medium" style={{ color: 'var(--text-color)' }}>Submit payment proof</span>
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="d-flex align-items-center p-3 rounded" style={{ 
                          backgroundColor: 'rgba(255,255,255,0.2)', 
                          backdropFilter: 'blur(10px)', 
                          border: '1px solid rgba(255,255,255,0.3)' 
                        }}>
                          <FontAwesomeIcon icon={solidIconMap.check} className="me-3" style={{ 
                            fontSize: '1.3rem',
                            color: 'var(--text-color)'
                          }} />
                          <span className="fw-medium" style={{ color: 'var(--text-color)' }}>Track payment status</span>
                        </div>
                      </div>
                      <div className="col-12">
                        <div className="d-flex align-items-center p-3 rounded" style={{ 
                          backgroundColor: 'rgba(255,255,255,0.2)', 
                          backdropFilter: 'blur(10px)', 
                          border: '1px solid rgba(255,255,255,0.3)' 
                        }}>
                          <FontAwesomeIcon icon={solidIconMap.check} className="me-3" style={{ 
                            fontSize: '1.3rem',
                            color: 'var(--text-color)'
                          }} />
                          <span className="fw-medium" style={{ color: 'var(--text-color)' }}>Mobile-friendly interface</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
