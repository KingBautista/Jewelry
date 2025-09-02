import { Link, useParams } from "react-router-dom";
import { useStateContext } from "../../contexts/AuthProvider";
import { useRef, useState, useEffect } from "react";
import axiosClient from "../../axios-client";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function Login() {
	const { vcode } = useParams();
	const emailRef = useRef();
	const passwordRef = useRef();

	const [isValidated, setValidated] = useState();
	const [message, setMessage] = useState();
	const [errors, setErrors] = useState();
	const [isLoading, setIsLoading] = useState();
	const [showPassword, setShowPassword] = useState(false);

	const { setToken, setUserRoutes } = useStateContext();

  useEffect(() => {
    localStorage.setItem('isReloaded', 'false');
  }, []);

	const validateUser = (code) => {
		const payload = {
			activation_key: code
		};

		axiosClient.post('/validate', payload)
		.then(({data}) => {
			if(data.message) {
				setMessage(data.message);
			}
		});
	};

	if(vcode) {
		validateUser(vcode);
	}

	const onSubmit = (ev) => {
		ev.preventDefault();
		setIsLoading(true);

		const payload = {
			email: emailRef.current.value,
			password: passwordRef.current.value,
		};

		axiosClient.post('/login', payload)
		.then(({data}) => {
			setToken(data.token);
			setUserRoutes(data.user.user_routes || []);
			localStorage.setItem('theme', data.user?.theme || 'light');
			localStorage.setItem('user_role_id', data.user.user_role_id);
			navigate('/dashboard');
		})
		.catch((errors) => {
			const response = errors.response;
			if(response && response.status === 422) {
				emailRef.current.value = null;
				passwordRef.current.value = null;
				setErrors(response.data.errors);
				setValidated('needs-validation was-validated');
				setIsLoading(false);
			}
		});
	};

	// Sanitize the message and errors
	const sanitizedMessage = message ? DOMPurify.sanitize(message) : '';
	const sanitizedErrors = errors ? Object.keys(errors).reduce((acc, key) => {
		acc[key] = DOMPurify.sanitize(errors[key]);
		return acc;
	}, {}) : {};

	useEffect(() => {
    localStorage.clear();
    localStorage.setItem('theme', 'light');
  }, []);

	return (
		<div className="col-lg-9">
			<div className="row g-0 rounded-4 overflow-hidden shadow-xl" style={{ backgroundColor: 'white', border: '1px solid rgba(0,0,0,0.05)' }}>
				{/* Login Form - Left Side */}
				<div className="col-lg-6">
					<div className="h-100 d-flex flex-column justify-content-center">
						<div className="p-5">
							{/* Header Section */}
							<div className="text-center mb-5">
								<FontAwesomeIcon icon={solidIconMap.user} className="text-primary fs-1 mb-4" style={{ filter: 'drop-shadow(0 4px 8px rgba(0,0,0,0.1))' }} />
								<h1 className="fw-bold text-dark mb-2" style={{ fontSize: '2.5rem', letterSpacing: '-0.5px' }}>Welcome Back</h1>
								<p className="text-muted mb-0 fs-5" style={{ opacity: 0.8 }}>Sign in to continue to your account</p>
							</div>

							<form onSubmit={onSubmit} className={isValidated}>
								{/* Success Message */}
								{sanitizedMessage && 
									<div className="alert alert-success border-0 shadow-sm" role="alert">
										<div className="d-flex align-items-center">
											<FontAwesomeIcon icon={solidIconMap.check} className="text-success me-2" />
											<p className="mb-0 fw-medium">{sanitizedMessage}</p>
										</div>
									</div>
								}
								
								{/* Error Messages */}
								{Object.keys(sanitizedErrors).length > 0 && 
									<div className="alert alert-danger border-0 shadow-sm" role="alert">
										<div className="d-flex align-items-center mb-2">
											<FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="text-danger me-2" />
											<span className="fw-medium">Please fix the following errors:</span>
										</div>
										{Object.keys(sanitizedErrors).map(key => (
											<div key={key} className="ms-4">• {sanitizedErrors[key]}</div>
										))}
									</div>
								}
								
								{/* Email Input */}
								<div className="mb-4">
									<label htmlFor="email" className="form-label fw-semibold text-dark mb-2" style={{ fontSize: '1.1rem' }}>Email Address</label>
									<div className="input-group input-group-lg shadow-sm">
										<span className="input-group-text bg-white border-end-0 border-2" style={{ borderColor: '#e9ecef' }}>
											<FontAwesomeIcon icon={solidIconMap.user} className="text-muted" />
										</span>
										<input 
											id="email"
											ref={emailRef} 
											className="form-control border-start-0 border-2" 
											type="email" 
											placeholder="Enter your email" 
											required
											style={{ fontSize: '1rem', borderColor: '#e9ecef' }}
										/>
									</div>
								</div>
								
								{/* Password Input */}
								<div className="mb-4">
									<label htmlFor="password" className="form-label fw-semibold text-dark mb-2" style={{ fontSize: '1.1rem' }}>Password</label>
									<div className="input-group input-group-lg shadow-sm">
										<span className="input-group-text bg-white border-end-0 border-2" style={{ borderColor: '#e9ecef' }}>
											<FontAwesomeIcon icon={solidIconMap.lock} className="text-muted" />
										</span>
										<input
											id="password"
											ref={passwordRef}
											className="form-control border-start-0 border-2"
											type={showPassword ? "text" : "password"}
											placeholder="Enter your password"
											required
											style={{ fontSize: '1rem', borderColor: '#e9ecef' }}
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
												accentColor: '#321fdb'
											}}
										/>
										<label htmlFor="showPassword" className="form-check-label text-muted fw-semibold" style={{ fontSize: '1rem' }}>
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
											borderRadius: '0.75rem',
											boxShadow: '0 4px 15px rgba(50, 31, 219, 0.3)',
											border: 'none'
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
									<Link to="/forgot-password" className="text-decoration-none text-muted fw-semibold" style={{ fontSize: '1rem', transition: 'all 0.3s ease' }}>
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
							background: 'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%)',
							minHeight: '500px'
						}}
					>
						{/* Background Pattern */}
						<div 
							className="position-absolute w-100 h-100"
							style={{
								backgroundImage: 'radial-gradient(circle at 25% 25%, rgba(255,255,255,0.15) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(255,255,255,0.15) 0%, transparent 50%), linear-gradient(45deg, rgba(255,255,255,0.05) 0%, transparent 50%)',
								backgroundSize: '300px 300px, 300px 300px, 100px 100px'
							}}
						></div>
						
						{/* Content */}
						<div className="text-center text-white position-relative">
							{/* Icon */}
							<div className="mb-5">
								<FontAwesomeIcon icon={solidIconMap.gem} className="text-white" style={{ fontSize: '4rem', filter: 'drop-shadow(0 8px 16px rgba(0,0,0,0.3))' }} />
							</div>
							
							{/* Welcome Text */}
							<h2 className="fw-bold mb-4 text-white" style={{ fontSize: '2.8rem', letterSpacing: '-1px', textShadow: '0 4px 8px rgba(0,0,0,0.3)' }}>
								Invoice & Payment Management System
							</h2>
							
							<p className="fs-4 mb-5 text-white" style={{ maxWidth: '450px', margin: '0 auto', opacity: 0.95, lineHeight: '1.6' }}>
								Comprehensive invoice and payment management for modern businesses
							</p>
							
							{/* Features List */}
							<div className="row g-4 text-start" style={{ maxWidth: '380px', margin: '0 auto' }}>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(255,255,255,0.1)', backdropFilter: 'blur(10px)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Advanced invoice management</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(255,255,255,0.1)', backdropFilter: 'blur(10px)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Secure payment processing</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(255,255,255,0.1)', backdropFilter: 'blur(10px)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Customer relationship tools</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(255,255,255,0.1)', backdropFilter: 'blur(10px)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Modern admin interface</span>
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