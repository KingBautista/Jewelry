import { Navigate, Outlet } from "react-router-dom";
import { useStateContext } from "../contexts/AuthProvider";
import { useEffect } from "react";

export default function GuestLayout() {
	const {token} = useStateContext();

	// Set default dark theme for guest layout
	useEffect(() => {
		// Set dark theme as default for guest pages
		document.documentElement.setAttribute('data-coreui-theme', 'dark');
		localStorage.setItem('theme', 'dark');
	}, []);

	if (token) {
		return <Navigate to="/" />
	}

	return (
		<div className="min-vh-100 d-flex flex-row align-items-center" style={{ backgroundColor: 'var(--surface-color)' }}>
			<div className="container">
				<div className="row justify-content-center">
					<Outlet/>
				</div>
			</div>      
		</div>
	) 
}