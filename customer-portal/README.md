# Customer Portal - Jewelry Management System

A modern, mobile-friendly customer portal built with React and Vite, featuring the elegant Champagne Elegance theme.

## 🎨 Features

### Authentication
- **Login via Email & Password** - Secure customer authentication
- **Forgot Password** - Email-based password reset functionality
- **Session Management** - Automatic token-based authentication

### Dashboard
- **Financial Overview** - View total invoices, paid amounts, and outstanding balances
- **Recent Invoices** - Quick access to latest invoice information
- **Payment Alerts** - Notifications for overdue invoices and upcoming dues
- **Quick Actions** - Direct links to common tasks

### Invoice Management
- **Invoice Listing** - View all customer invoices with filtering and search
- **Invoice Details** - Detailed view of invoice items, payment terms, and status
- **PDF Download** - Download invoice PDFs directly from the portal
- **Payment Status** - Real-time payment status tracking

### Payment Submission
- **Invoice Selection** - Choose from available unpaid invoices
- **Payment Details** - Enter amount paid, reference number, and payment method
- **Receipt Upload** - Upload multiple receipt images as proof of payment
- **Status Tracking** - Monitor payment submission status (pending, approved, rejected)

### Payment History
- **Submission History** - View all past payment submissions
- **Status Updates** - Track approval/rejection status
- **Receipt Viewing** - Access uploaded receipt images
- **Timeline Tracking** - See submission and review dates

### Profile Management
- **Account Information** - Update personal details and contact information
- **Security Settings** - Manage account security preferences
- **Account Status** - View account status and membership information

## 🎨 Champagne Elegance Theme

The portal features a sophisticated Champagne Elegance color scheme:

- **Primary Colors**: Champagne Gold (#D4AF37), Cream (#F7E7CE)
- **Accent Colors**: Dark Gold (#B8860B), Light Champagne (#FFF8DC)
- **Surface Colors**: Clean whites and subtle grays
- **Typography**: Modern, readable fonts with proper hierarchy
- **Responsive Design**: Mobile-first approach with elegant desktop layouts

## 🚀 Getting Started

### Prerequisites
- Node.js 18+ 
- npm or yarn
- Access to the Jewelry Management System API

### Installation

1. **Clone the repository**
   ```bash
   cd customer-portal
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Configure environment**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   
   # Edit .env file with your API base URL
   # Default: http://127.0.0.1:8000
   ```

4. **Start development server**
   ```bash
   npm run dev
   ```

5. **Build for production**
   ```bash
   npm run build
   ```

## 📱 Mobile Responsiveness

The customer portal is fully responsive and optimized for:
- **Mobile Phones** (320px - 768px)
- **Tablets** (768px - 1024px) 
- **Desktop** (1024px+)

Key mobile features:
- Touch-friendly interface
- Swipe gestures for navigation
- Optimized form inputs
- Responsive image handling
- Mobile-specific layouts

## 🔧 API Integration

### Authentication Endpoints
- `POST /api/customer/login` - Customer login
- `POST /api/customer/forgot-password` - Password reset

### Protected Endpoints
- `GET /api/customer/user` - Get customer profile
- `PUT /api/customer/user` - Update customer profile
- `GET /api/customer/dashboard/overview` - Dashboard data
- `GET /api/customer/invoices` - Customer invoices
- `GET /api/customer/invoices/{id}` - Invoice details
- `GET /api/customer/invoices/{id}/pdf` - Download PDF
- `POST /api/customer/payment-submission` - Submit payment
- `GET /api/customer/payment-submissions` - Payment history

## 📧 Email Notifications

The system sends automated emails for:
- **Password Reset** - New temporary password
- **Payment Submission** - Admin notification of new payment
- **Payment Status Updates** - Approval/rejection notifications
- **Invoice Alerts** - Overdue and upcoming due date reminders

## 🧪 Testing

### Running Tests
```bash
# Backend tests
php artisan test tests/Feature/CustomerPortalTest.php
php artisan test tests/Unit/CustomerPortalControllerTest.php

# Frontend tests (if configured)
npm run test
```

### Test Coverage
- **Authentication** - Login, logout, password reset
- **Authorization** - Customer access control
- **API Endpoints** - All customer portal endpoints
- **Data Validation** - Form validation and error handling
- **Security** - Cross-customer data access prevention

## 🔒 Security Features

- **JWT Token Authentication** - Secure API access
- **Role-Based Access** - Customer-only access to customer data
- **Data Validation** - Server-side validation for all inputs
- **File Upload Security** - Secure receipt image handling
- **CSRF Protection** - Cross-site request forgery prevention
- **XSS Protection** - Input sanitization and output encoding

## 📊 Performance Optimizations

- **Lazy Loading** - Components loaded on demand
- **Image Optimization** - Compressed receipt images
- **Caching** - API response caching
- **Bundle Splitting** - Optimized JavaScript bundles
- **CDN Ready** - Static asset optimization

## 🎯 User Experience

### Key UX Features
- **Intuitive Navigation** - Clear menu structure
- **Visual Feedback** - Loading states and success messages
- **Error Handling** - User-friendly error messages
- **Accessibility** - WCAG compliant design
- **Fast Loading** - Optimized performance

### Design Principles
- **Minimalist Design** - Clean, uncluttered interface
- **Consistent Branding** - Champagne Elegance theme throughout
- **User-Centric** - Designed for customer needs
- **Professional** - Business-appropriate styling

## 🚀 Deployment

### Production Build
```bash
npm run build
```

### Environment Variables
```env
VITE_API_BASE_URL=http://127.0.0.1:8000
```

The `.env` file should contain your API base URL. The customer portal will automatically append `/api` to the base URL for all API requests.

### Server Configuration
- Serve static files from `dist/` directory
- Configure proper MIME types
- Enable gzip compression
- Set up HTTPS for security

## 📈 Future Enhancements

- **Real-time Notifications** - WebSocket integration
- **Advanced Filtering** - More invoice filtering options
- **Bulk Actions** - Multiple payment submissions
- **Export Features** - PDF/CSV export capabilities
- **Mobile App** - Native mobile application
- **Multi-language** - Internationalization support

## 🤝 Support

For technical support or questions:
- **Email**: support@jewelrymanagement.com
- **Documentation**: Internal wiki
- **Issue Tracking**: GitHub issues

## 📄 License

This project is proprietary software for the Jewelry Management System.
