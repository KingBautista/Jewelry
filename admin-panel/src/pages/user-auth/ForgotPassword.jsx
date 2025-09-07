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
		<div className="col-lg-9">
			<div className="row g-0 rounded-4 overflow-hidden shadow-xl" style={{ backgroundColor: '#1a1a1a', border: '1px solid rgba(255,255,255,0.1)' }}>
				{/* Forgot Password Form - Left Side */}
				<div className="col-lg-6">
					<div className="h-100 d-flex flex-column justify-content-center">
						<div className="p-5">
							{/* Header Section */}
							<div className="text-center mb-5">
								<FontAwesomeIcon icon={solidIconMap.key} className="text-light fs-1 mb-4" style={{ filter: 'drop-shadow(0 4px 8px rgba(0,0,0,0.3))' }} />
								<h1 className="fw-bold text-white mb-2" style={{ fontSize: '2.5rem', letterSpacing: '-0.5px' }}>Forgot Password?</h1>
								<p className="text-light mb-0 fs-5" style={{ opacity: 0.8 }}>Enter your email to reset your password</p>
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
									<label htmlFor="email" className="form-label fw-semibold text-white mb-2" style={{ fontSize: '1.1rem' }}>Email Address</label>
									<div className="input-group input-group-lg shadow-sm">
										<span className="input-group-text bg-dark border-end-0 border-2" style={{ borderColor: '#404040', color: '#9ca3af' }}>
											<FontAwesomeIcon icon={solidIconMap.envelope} className="text-light" />
										</span>
										<input 
											id="email"
											ref={emailRef}
											className={`form-control border-start-0 border-2 bg-dark text-white ${errors && errors.email ? 'is-invalid' : ''}`}
											type="email"
											placeholder="Enter your email"
											required
											style={{ fontSize: '1rem', borderColor: '#404040' }}
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
										className="btn w-100 fw-bold" 
										type="submit"
										disabled={isLoading}
										style={{ 
											padding: '0.8rem 1.5rem',
											fontSize: '1.1rem',
											borderRadius: '0.75rem',
											backgroundColor: '#374151',
											borderColor: '#4b5563',
											color: 'white',
											boxShadow: '0 4px 15px rgba(75, 85, 99, 0.3)',
											border: '1px solid #4b5563'
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
									<Link to="/login" className="text-decoration-none text-light fw-semibold" style={{ fontSize: '1rem', transition: 'all 0.3s ease' }}>
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
						className="h-100 d-flex align-items-center justify-content-center"
						style={{ 
							background: 'linear-gradient(135deg, #1f2937 0%, #374151 50%, #4b5563 100%)',
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
						<div className="text-center text-white position-relative">
							{/* Icon */}
							<div className="mb-5">
								<FontAwesomeIcon icon={solidIconMap.shieldAlt} className="text-white" style={{ fontSize: '4rem', filter: 'drop-shadow(0 8px 16px rgba(0,0,0,0.3))' }} />
							</div>
							
							{/* Welcome Text */}
							<h2 className="fw-bold mb-4 text-white" style={{ fontSize: '2.8rem', letterSpacing: '-1px', textShadow: '0 4px 8px rgba(0,0,0,0.3)' }}>
								Password Recovery
							</h2>
							
							<p className="fs-4 text-white" style={{ maxWidth: '450px', margin: '0 auto', opacity: 0.95, lineHeight: '1.6' }}>
								Secure password reset process to protect your account
							</p>
							
							{/* Features List */}
							<div className="row g-4 text-start" style={{ maxWidth: '380px', margin: '0 auto' }}>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(0,0,0,0.2)', backdropFilter: 'blur(10px)', border: '1px solid rgba(255,255,255,0.1)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Secure email verification</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(0,0,0,0.2)', backdropFilter: 'blur(10px)', border: '1px solid rgba(255,255,255,0.1)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Quick password reset</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(0,0,0,0.2)', backdropFilter: 'blur(10px)', border: '1px solid rgba(255,255,255,0.1)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Account security protection</span>
									</div>
								</div>
								<div className="col-12">
									<div className="d-flex align-items-center p-3 rounded" style={{ backgroundColor: 'rgba(0,0,0,0.2)', backdropFilter: 'blur(10px)', border: '1px solid rgba(255,255,255,0.1)' }}>
										<FontAwesomeIcon icon={solidIconMap.check} className="text-white me-3" style={{ fontSize: '1.3rem' }} />
										<span className="text-white fw-medium">Easy access restoration</span>
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