import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import axiosClient from "../../axios-client";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function ForgotPassword() {	
	const emailRef = useRef();

	const [isValidated, setValidated] = useState('');
	const [isLoading, setIsLoading] = useState(false);
	const [errors, setErrors] = useState(null);
	const [message, setMessage] = useState('');

	const onSubmit = (ev) => {
		ev.preventDefault();
		setIsLoading(true);

		const payload = {
			email: emailRef.current.value,
		};

		axiosClient.post('/generate-password', payload)
			.then(({ data }) => {
				if (data) {
					emailRef.current.value = null;
					setMessage(data.message);
					setValidated('needs-validation');
				}
			})
			.catch((errors) => {
				const response = errors.response;
				if (response && response.status === 422) {
					setErrors(response.data.errors);
				}
			})
			.finally(() => setIsLoading(false));
	};

	// Sanitize the message and errors
	const sanitizedMessage = DOMPurify.sanitize(message);
	const sanitizedErrors = errors ? Object.keys(errors).reduce((acc, key) => {
		acc[key] = DOMPurify.sanitize(errors[key]);
		return acc;
	}, {}) : {};

	return (
		<div className="col-lg-9 auth-page">
			<div className="row g-0 rounded-4 overflow-hidden shadow-xl auth-surface auth-border auth-card-shadow">
				{/* Forgot Password Form - Left Side */}
				<div className="col-lg-6">
					<div className="h-100 d-flex flex-column justify-content-center">
						<div className="p-5">
							{/* Header Section */}
							<div className="text-center mb-5">
								<FontAwesomeIcon icon={solidIconMap.key} className="fs-1 mb-4 auth-text" style={{ 
									filter: 'drop-shadow(0 4px 8px rgba(0,0,0,0.3))'
								}} />
								<h1 className="fw-bold mb-2 auth-text" style={{ 
									fontSize: '2.5rem', 
									letterSpacing: '-0.5px'
								}}>Forgot Password?</h1>
								<p className="mb-0 fs-5 auth-text-muted" style={{ 
									opacity: 0.8
								}}>Enter your email to reset your password</p>
							</div>

							<form onSubmit={onSubmit} className={isValidated}>
								{/* Success Message */}
								{sanitizedMessage && 
									<div className="alert border-0 shadow-sm" role="alert" style={{ 
										backgroundColor: 'rgba(25, 135, 84, 0.1)', 
										border: '1px solid rgba(25, 135, 84, 0.3)',
										color: '#d1e7dd'
									}}>
										<div className="d-flex align-items-center">
											<FontAwesomeIcon icon={solidIconMap.check} className="me-2" style={{ color: '#198754' }} />
											<p className="mb-0 fw-medium" style={{ color: '#d1e7dd' }}>{sanitizedMessage}</p>
										</div>
									</div>
								}
								
								{/* Error Messages */}
								{Object.keys(sanitizedErrors).length > 0 && 
									<div className="alert border-0 shadow-sm" role="alert" style={{ 
										backgroundColor: 'rgba(220, 53, 69, 0.1)', 
										border: '1px solid rgba(220, 53, 69, 0.3)',
										color: '#f8d7da'
									}}>
										<div className="d-flex align-items-center mb-2">
											<FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-2" style={{ color: '#dc3545' }} />
											<span className="fw-medium" style={{ color: '#f8d7da' }}>Please fix the following errors:</span>
										</div>
										{Object.keys(sanitizedErrors).map(key => (
											<div key={key} className="ms-4" style={{ color: '#f8d7da' }}>• {sanitizedErrors[key]}</div>
										))}
									</div>
								}
								
								{/* Email Input */}
								<div className="mb-4">
									<label htmlFor="email" className="form-label fw-semibold mb-2 auth-text" style={{ 
										fontSize: '1.1rem'
									}}>Email Address</label>
									<div className="input-group input-group-lg shadow-sm">
										<span className="input-group-text border-end-0 border-2 auth-input-group-text auth-border">
											<FontAwesomeIcon icon={solidIconMap.envelope} />
										</span>
										<input 
											id="email"
											ref={emailRef}
											className={`form-control border-start-0 border-2 auth-input auth-border ${errors && errors.email ? 'is-invalid' : ''}`}
											type="email"
											placeholder="Enter your email"
											required
											style={{ 
												fontSize: '1rem'
											}}
										/>
										{errors && errors.email && (
											<div className="invalid-feedback">
												{sanitizedErrors.email}
											</div>
										)}
									</div>
								</div>
								
								{/* Submit Button */}
								<div className="mb-4">
									<button 
										className="btn w-100 fw-bold auth-primary auth-shadow" 
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
												Sending Reset Link...
											</>
										) : (
											<>
												<FontAwesomeIcon icon={solidIconMap.paperPlane} className="me-2" />
												Get New Password
											</>
										)}
									</button>
								</div>
								
								{/* Back to Login Link */}
								<div className="text-center">
									<Link to="/login" className="text-decoration-none fw-semibold auth-link" style={{ 
										fontSize: '1rem', 
										transition: 'all 0.3s ease'
									}}>
										<FontAwesomeIcon icon={solidIconMap.arrowLeft} className="me-2" />
										Back to Login
									</Link>
								</div>
							</form>
						</div>
					</div>
				</div>
				
				{/* Content Section - Right Side */}
				<div className="col-lg-6 position-relative">
					<div 
						className="h-100 d-flex align-items-center justify-content-center auth-surface-secondary"
						style={{ 
							minHeight: '500px'
						}}
					>
						{/* Background Pattern */}
						<div 
							className="position-absolute w-100 h-100"
							style={{
								backgroundImage: 'radial-gradient(circle at 25% 25%, rgba(255,255,255,0.05) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 0%, transparent 50%), linear-gradient(45deg, rgba(255,255,255,0.02) 0%, transparent 50%)',
								backgroundSize: '300px 300px, 300px 300px, 100px 100px'
							}}
						></div>
						
						{/* Content */}
						<div className="text-center position-relative">
							{/* Icon */}
							<div className="mb-5">
								<FontAwesomeIcon icon={solidIconMap.shieldAlt} className="auth-text" style={{ 
									fontSize: '4rem', 
									filter: 'drop-shadow(0 8px 16px rgba(0,0,0,0.3))'
								}} />
							</div>
							
							{/* Welcome Text */}
							<h2 className="fw-bold mb-4 auth-text" style={{ 
								fontSize: '2.8rem', 
								letterSpacing: '-1px', 
								textShadow: '0 4px 8px rgba(0,0,0,0.3)'
							}}>
								Password Recovery
							</h2>
							
							<p className="fs-4 auth-text-muted" style={{ 
								maxWidth: '450px', 
								margin: '0 auto', 
								opacity: 0.95, 
								lineHeight: '1.6'
							}}>
								Secure password reset process to protect your account
							</p>
							
							{/* Features List */}
							<div className="row g-4 text-start" style={{ maxWidth: '380px', margin: '0 auto' }}>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded auth-feature-item auth-border" style={{ 
										backdropFilter: 'blur(10px)'
									}}>
										<FontAwesomeIcon icon={solidIconMap.check} className="me-3 auth-feature-icon" style={{ 
											fontSize: '1.3rem'
										}} />
										<span className="fw-medium auth-feature-text">Secure email verification</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded auth-feature-item auth-border" style={{ 
										backdropFilter: 'blur(10px)'
									}}>
										<FontAwesomeIcon icon={solidIconMap.check} className="me-3 auth-feature-icon" style={{ 
											fontSize: '1.3rem'
										}} />
										<span className="fw-medium auth-feature-text">Quick password reset</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded auth-feature-item auth-border" style={{ 
										backdropFilter: 'blur(10px)'
									}}>
										<FontAwesomeIcon icon={solidIconMap.check} className="me-3 auth-feature-icon" style={{ 
											fontSize: '1.3rem'
										}} />
										<span className="fw-medium auth-feature-text">Account security protection</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded auth-feature-item auth-border" style={{ 
										backdropFilter: 'blur(10px)'
									}}>
										<FontAwesomeIcon icon={solidIconMap.check} className="me-3 auth-feature-icon" style={{ 
											fontSize: '1.3rem'
										}} />
										<span className="fw-medium auth-feature-text">Easy access restoration</span>
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