import { Suspense, lazy } from 'react';
import { useLocation } from 'react-router-dom';

// Dynamically import pages
const Dashboard = lazy(() => import('./Dashboard'));

const Users = lazy(() => import('./user-management/Users'));
const UserForm = lazy(() => import('./user-management/UserForm'));
const Roles = lazy(() => import('./user-management/Roles'));
const RoleForm = lazy(() => import('./user-management/RoleForm'));
const Profile = lazy(() => import('./user-management/Profile'));

const Library = lazy(() => import('./content-management/Library'));
const LibraryForm = lazy(() => import('./content-management/LibraryForm'));
const MediaForm = lazy(() => import('./content-management/MediaForm'));

const Navigations = lazy(() => import('./system-settings/Navigations'));
const NavigationForm = lazy(() => import('./system-settings/NavigationForm'));

// Financial Management Components
const Taxes = lazy(() => import('./financial-management/Taxes'));
const TaxForm = lazy(() => import('./financial-management/TaxForm'));
const Fees = lazy(() => import('./financial-management/Fees'));
const FeeForm = lazy(() => import('./financial-management/FeeForm'));
const Discounts = lazy(() => import('./financial-management/Discounts'));
const DiscountForm = lazy(() => import('./financial-management/DiscountForm'));
const PaymentTerms = lazy(() => import('./financial-management/PaymentTerms'));
const PaymentTermForm = lazy(() => import('./financial-management/PaymentTermForm'));
const PaymentMethods = lazy(() => import('./financial-management/PaymentMethods'));
const PaymentMethodForm = lazy(() => import('./financial-management/PaymentMethodForm'));

// Customer Management Components
const Customers = lazy(() => import('./customer-management/Customers'));
const CustomerForm = lazy(() => import('./customer-management/CustomerForm'));

// Invoice Management Components
const Invoices = lazy(() => import('./invoice-management/Invoices'));
const InvoiceForm = lazy(() => import('./invoice-management/InvoiceForm'));

// Payment Management Components
const Payments = lazy(() => import('./payment-management/Payments'));
const PaymentForm = lazy(() => import('./payment-management/PaymentForm'));

// Mapping paths to components
const routeMap = {
  '/dashboard': Dashboard,
  '/user-management/users': Users,
  '/user-management/users/create': UserForm,
  '/user-management/users/:id': UserForm,
  '/user-management/roles': Roles,
  '/user-management/roles/create': RoleForm,
  '/user-management/roles/:id': RoleForm,
  '/content-management/media-library': Library,
  '/content-management/media-library/upload': LibraryForm,
  '/content-management/media-library/:id': MediaForm,
  '/system-settings/navigation': Navigations,
  '/system-settings/navigation/create': NavigationForm,
  '/system-settings/navigation/:id': NavigationForm,
  '/profile': Profile,
  // Financial Management Routes
  '/financial-management/taxes': Taxes,
  '/financial-management/taxes/create': TaxForm,
  '/financial-management/taxes/:id': TaxForm,
  '/financial-management/fees': Fees,
  '/financial-management/fees/create': FeeForm,
  '/financial-management/fees/:id': FeeForm,
  '/financial-management/discounts': Discounts,
  '/financial-management/discounts/create': DiscountForm,
  '/financial-management/discounts/:id': DiscountForm,
  '/financial-management/payment-terms': PaymentTerms,
  '/financial-management/payment-terms/create': PaymentTermForm,
  '/financial-management/payment-terms/:id': PaymentTermForm,
  '/financial-management/payment-methods': PaymentMethods,
  '/financial-management/payment-methods/create': PaymentMethodForm,
  '/financial-management/payment-methods/:id': PaymentMethodForm,
  // Customer Management Routes
  '/customer-management/customers': Customers,
  '/customer-management/customers/create': CustomerForm,
  '/customer-management/customers/:id': CustomerForm,
  '/customer-management/customers/:id/edit': CustomerForm,
  // Invoice Management Routes
  '/invoice-management/invoices': Invoices,
  '/invoice-management/invoices/create': InvoiceForm,
  '/invoice-management/invoices/:id': InvoiceForm,
  '/invoice-management/invoices/:id/edit': InvoiceForm,
  // Payment Management Routes
  '/payment-management/payments': Payments,
  '/payment-management/payments/create': PaymentForm,
  '/payment-management/payments/:id': PaymentForm,
  '/payment-management/payments/:id/edit': PaymentForm,
};

const Index = () => {
  const location = useLocation();

  // Helper function to determine which component to render
  const getComponentToRender = () => {
    // Get the current pathname
    const currentPath = location.pathname;
  
    // Sort routes by length in descending order so the longest match comes first
    const sortedRoutes = Object.keys(routeMap).sort((a, b) => b.length - a.length);
  
    // Find the first route that matches the current path
    const matchedRoute = sortedRoutes.find((path) => {
      // Convert the route with dynamic params (e.g. /:id) to a regular expression
      const regexPath = path.replace(/:([^/]+)/g, '([^/]+)'); // Convert ":id" to a regex capturing group
      const pathRegex = new RegExp(`^${regexPath}$`);
      return pathRegex.test(currentPath);
    });
    
    // Return the corresponding component from the routeMap if a match is found, otherwise null
    return matchedRoute ? routeMap[matchedRoute] : null;
  };

  const ComponentToRender = getComponentToRender();

  return (
    <>
      <Suspense fallback={<div className="col-12 text-center">Loading...</div>}>
        {ComponentToRender ? <ComponentToRender /> : <div className="card text-center p-5"><h4>404 - Page Not Found</h4></div> }
      </Suspense>
    </>
  );
};

export default Index;