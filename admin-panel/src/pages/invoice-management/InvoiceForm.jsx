import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import axiosClient from "../../axios-client";
import ToastMessage from "../../components/ToastMessage";
import Field from "../../components/Field";
import DOMPurify from 'dompurify';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../../utils/solidIcons';

export default function InvoiceForm() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toastAction = useRef();
  
  const [buttonText, setButtonText] = useState('Create Invoice');
  const [invoice, setInvoice] = useState({
    id: null,
    invoice_number: '',
    customer_id: '',
    products: [
      {
        product_name: '',
        description: '',
        price: '',
        product_images: []
      }
    ],
    payment_term_id: '',
    tax_id: '',
    fee_id: '',
    discount_id: '',
    shipping_address: '',
    issue_date: '',
    due_date: '',
    status: 'draft',
    notes: '',
    active: true
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [selectedFiles, setSelectedFiles] = useState([[]]); // Array of arrays for each product
  
  // Modal state
  const [showProductModal, setShowProductModal] = useState(false);
  const [editingProductIndex, setEditingProductIndex] = useState(null);
  const [currentProduct, setCurrentProduct] = useState({
    product_name: '',
    description: '',
    price: '',
    product_images: []
  });
  const [currentProductFiles, setCurrentProductFiles] = useState([]);
  
  // Dropdown data
  const [customers, setCustomers] = useState([]);
  const [paymentTerms, setPaymentTerms] = useState([]);
  const [taxes, setTaxes] = useState([]);
  const [fees, setFees] = useState([]);
  const [discounts, setDiscounts] = useState([]);

  // Helper functions for managing products
  const openAddProductModal = () => {
    setCurrentProduct({
      product_name: '',
      description: '',
      price: '',
      product_images: []
    });
    setCurrentProductFiles([]);
    setEditingProductIndex(null);
    setShowProductModal(true);
  };

  const openEditProductModal = (index) => {
    const product = invoice.products[index];
    setCurrentProduct({ ...product });
    setCurrentProductFiles(selectedFiles[index] || []);
    setEditingProductIndex(index);
    setShowProductModal(true);
  };

  const saveProduct = () => {
    if (!currentProduct.product_name || !currentProduct.price) {
      toastAction.current.showToast('Please fill in product name and price', 'warning');
      return;
    }

    const amount = parseFloat(currentProduct.price);
    if (isNaN(amount) || amount < 0) {
      toastAction.current.showToast('Price must be a positive number', 'warning');
      return;
    }

    const updatedProducts = [...invoice.products];
    const updatedFiles = [...selectedFiles];

    if (editingProductIndex !== null) {
      // Editing existing product
      updatedProducts[editingProductIndex] = { ...currentProduct };
      updatedFiles[editingProductIndex] = [...currentProductFiles];
    } else {
      // Adding new product
      updatedProducts.push({ ...currentProduct });
      updatedFiles.push([...currentProductFiles]);
    }

    setInvoice({ ...invoice, products: updatedProducts });
    setSelectedFiles(updatedFiles);
    setShowProductModal(false);
    setCurrentProduct({
      product_name: '',
      description: '',
      price: '',
      product_images: []
    });
    setCurrentProductFiles([]);
    setEditingProductIndex(null);
  };

  const removeProduct = (index) => {
    if (invoice.products.length > 1) {
      if (window.confirm('Are you sure you want to remove this product?')) {
        const updatedProducts = invoice.products.filter((_, i) => i !== index);
        const updatedFiles = selectedFiles.filter((_, i) => i !== index);
        setInvoice({ ...invoice, products: updatedProducts });
        setSelectedFiles(updatedFiles);
      }
    }
  };

  const updateCurrentProduct = (field, value) => {
    setCurrentProduct({ ...currentProduct, [field]: value });
  };

  const updateCurrentProductImages = (images) => {
    setCurrentProduct({ ...currentProduct, product_images: images });
  };

  // Calculate total amount with tax, fee, and discount
  const calculateSubtotal = () => {
    if (!invoice.products || invoice.products.length === 0) return 0;
    return invoice.products.reduce((total, product) => {
      const price = parseFloat(product.price) || 0;
      return total + price;
    }, 0);
  };

  const calculateTaxAmount = () => {
    try {
      const subtotal = calculateSubtotal();
      if (!taxes || !Array.isArray(taxes) || taxes.length === 0) return 0;
      if (!invoice.tax_id) return 0;
      
      const selectedTax = taxes.find(tax => tax.id == invoice.tax_id);
      if (!selectedTax) return 0;
      
      const rate = parseFloat(selectedTax.rate) || 0;
      if (selectedTax.type === 'percentage') {
        return (subtotal * rate) / 100;
      } else {
        return rate;
      }
    } catch (error) {
      console.error('Error calculating tax amount:', error);
      return 0;
    }
  };

  const calculateFeeAmount = () => {
    try {
      const subtotal = calculateSubtotal();
      if (!fees || !Array.isArray(fees) || fees.length === 0) return 0;
      if (!invoice.fee_id) return 0;
      
      const selectedFee = fees.find(fee => fee.id == invoice.fee_id);
      if (!selectedFee) return 0;
      
      const amount = parseFloat(selectedFee.amount) || 0;
      if (selectedFee.type === 'percentage') {
        return (subtotal * amount) / 100;
      } else {
        return amount;
      }
    } catch (error) {
      console.error('Error calculating fee amount:', error);
      return 0;
    }
  };

  const calculateDiscountAmount = () => {
    try {
      const subtotal = calculateSubtotal();
      if (!discounts || !Array.isArray(discounts) || discounts.length === 0) return 0;
      if (!invoice.discount_id) return 0;
      
      const selectedDiscount = discounts.find(discount => discount.id == invoice.discount_id);
      if (!selectedDiscount) return 0;
      
      const amount = parseFloat(selectedDiscount.amount) || 0;
      if (selectedDiscount.type === 'percentage') {
        return (subtotal * amount) / 100;
      } else {
        return amount;
      }
    } catch (error) {
      console.error('Error calculating discount amount:', error);
      return 0;
    }
  };

  const calculateTotal = () => {
    try {
      const subtotal = calculateSubtotal();
      const taxAmount = calculateTaxAmount();
      const feeAmount = calculateFeeAmount();
      const discountAmount = calculateDiscountAmount();
      
      return subtotal + taxAmount + feeAmount - discountAmount;
    } catch (error) {
      console.error('Error calculating total:', error);
      return 0;
    }
  };

  // Load dropdown data
  useEffect(() => {
    const loadDropdownData = async () => {
      try {
        const [customersRes, paymentTermsRes, taxesRes, feesRes, discountsRes] = await Promise.all([
          axiosClient.get('/options/customers'),
          axiosClient.get('/options/payment-terms'),
          axiosClient.get('/options/taxes'),
          axiosClient.get('/options/fees'),
          axiosClient.get('/options/discounts')
        ]);

        const customersData = customersRes.data || [];
        const paymentTermsData = paymentTermsRes.data || [];
        const taxesData = taxesRes.data || [];
        const feesData = feesRes.data || [];
        const discountsData = discountsRes.data || [];

        console.log('Loaded data:', {
          customers: customersData.length,
          paymentTerms: paymentTermsData.length,
          taxes: taxesData.length,
          fees: feesData.length,
          discounts: discountsData.length
        });

        setCustomers(customersData);
        setPaymentTerms(paymentTermsData);
        setTaxes(taxesData);
        setFees(feesData);
        setDiscounts(discountsData);
      } catch (error) {
        console.error('Error loading dropdown data:', error);
      }
    };

    loadDropdownData();
  }, []);

  // Load invoice data for editing (if ID exists)
  useEffect(() => {
    if (id) {
      setButtonText('Save');
      setIsLoading(true);
      axiosClient.get(`/invoice-management/invoices/${id}`)
        .then(({ data }) => {
          const invoiceData = data.data || data;
          
          // Ensure dates are in the correct format for HTML date inputs
          if (invoiceData.issue_date) {
            // If the date is already in YYYY-MM-DD format, use it as is
            // If it's in a different format, convert it
            const issueDate = new Date(invoiceData.issue_date);
            if (!isNaN(issueDate.getTime())) {
              invoiceData.issue_date = issueDate.toISOString().split('T')[0];
            }
          }
          
          if (invoiceData.due_date) {
            const dueDate = new Date(invoiceData.due_date);
            if (!isNaN(dueDate.getTime())) {
              invoiceData.due_date = dueDate.toISOString().split('T')[0];
            }
          }
          
          // Handle products - if it's a single product, convert to array
          if (invoiceData.products && Array.isArray(invoiceData.products)) {
            // Already in correct format
          } else if (invoiceData.items && Array.isArray(invoiceData.items)) {
            // Convert items to products format
            invoiceData.products = invoiceData.items.map(item => ({
              product_name: item.product_name,
              description: item.description || '',
              price: item.price || '',
              product_images: item.product_images || []
            }));
          } else if (invoiceData.product_name) {
            // Convert single product to array format
            invoiceData.products = [{
              product_name: invoiceData.product_name,
              description: invoiceData.description || '',
              price: invoiceData.price || '',
              product_images: invoiceData.product_images || []
            }];
            // Remove old single product fields
            delete invoiceData.product_name;
            delete invoiceData.description;
            delete invoiceData.price;
            delete invoiceData.product_images;
          } else {
            // Default to single empty product
            invoiceData.products = [{
              product_name: '',
              description: '',
              price: '',
              product_images: []
            }];
          }
          
          // Initialize selectedFiles array to match products
          const filesArray = invoiceData.products.map(() => []);
          setSelectedFiles(filesArray);
          
          setInvoice(invoiceData);
          setIsLoading(false);
          setIsActive(invoiceData.active);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  }, [id]);

  // Handle form submission
  const onSubmit = (ev) => {
    ev.preventDefault();
    
    // Validate required fields
    if (!invoice.customer_id) {
      toastAction.current.showToast('Please select a customer', 'warning');
      return;
    }

    // Validate products
    for (let i = 0; i < invoice.products.length; i++) {
      const product = invoice.products[i];
      if (!product.product_name || !product.price) {
        toastAction.current.showToast(`Please fill in product name and price for product ${i + 1}`, 'warning');
        return;
      }

      const amount = parseFloat(product.price);
      if (isNaN(amount) || amount < 0) {
        toastAction.current.showToast(`Price must be a positive number for product ${i + 1}`, 'warning');
        return;
      }
    }

    setIsLoading(true);

    // Create FormData for file uploads
    const formData = new FormData();
    
    // Add all invoice data with proper formatting - only append if value exists
    if (invoice.invoice_number) formData.append('invoice_number', invoice.invoice_number);
    if (invoice.customer_id) formData.append('customer_id', invoice.customer_id);
    if (invoice.payment_term_id) formData.append('payment_term_id', invoice.payment_term_id);
    if (invoice.tax_id) formData.append('tax_id', invoice.tax_id);
    if (invoice.fee_id) formData.append('fee_id', invoice.fee_id);
    if (invoice.discount_id) formData.append('discount_id', invoice.discount_id);
    if (invoice.shipping_address) formData.append('shipping_address', invoice.shipping_address);
    if (invoice.issue_date) formData.append('issue_date', invoice.issue_date);
    if (invoice.due_date) formData.append('due_date', invoice.due_date);
    if (invoice.status) formData.append('status', invoice.status);
    if (invoice.notes) formData.append('notes', invoice.notes);
    formData.append('active', isActive);
    
    // Add invoice ID for updates
    if (invoice.id) {
      formData.append('invoice_id', invoice.id);
    }
    
    // Add products data
    invoice.products.forEach((product, productIndex) => {
      formData.append(`products[${productIndex}][product_name]`, product.product_name);
      formData.append(`products[${productIndex}][description]`, product.description || '');
      formData.append(`products[${productIndex}][price]`, product.price);
      
      // Add product images for this product
      if (selectedFiles[productIndex] && selectedFiles[productIndex].length > 0) {
        selectedFiles[productIndex].forEach((file, fileIndex) => {
          formData.append(`products[${productIndex}][product_images][${fileIndex}]`, file);
        });
      }
    });

    // Let axios handle the Content-Type header automatically
    const config = {};

    // Use POST for both create and update to avoid FormData parsing issues with PUT
    const request = axiosClient.post('/invoice-management/invoices', formData, config);

    request
      .then(() => {
        const action = invoice.id ? 'updated' : 'added';
        toastAction.current.showToast(`Invoice has been ${action}.`, 'success');
        setIsLoading(false);
        setTimeout(() => navigate('/invoice-management/invoices'), 2000);
      })
      .catch((errors) => {
        toastAction.current.showError(errors.response);
        setIsLoading(false);
      });
  };

  // Handle delete
  const handleDelete = () => {
    if (!invoice.id) return;
    
    if (window.confirm('Are you sure you want to delete this invoice?')) {
      setIsLoading(true);
      axiosClient.delete(`/invoice-management/invoices/${invoice.id}`)
        .then(() => {
          toastAction.current.showToast('Invoice has been deleted.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/invoice-management/invoices'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle cancel invoice
  const handleCancel = () => {
    if (!invoice.id) return;
    
    if (window.confirm('Are you sure you want to cancel this invoice?')) {
      setIsLoading(true);
      axiosClient.patch(`/invoice-management/invoices/${invoice.id}/cancel`)
        .then(() => {
          toastAction.current.showToast('Invoice has been cancelled.', 'success');
          setIsLoading(false);
          setTimeout(() => navigate('/invoice-management/invoices'), 2000);
        })
        .catch((errors) => {
          toastAction.current.showError(errors.response);
          setIsLoading(false);
        });
    }
  };

  // Handle download PDF
  const handleDownloadPdf = async () => {
    if (!invoice.id) return;
    
    try {
      setIsLoading(true);
      toastAction.current.showToast('Generating PDF...', 'info');
      
      // Get the token for authentication
      const token = localStorage.getItem('ACCESS_TOKEN');
      
      // Make the request with proper headers
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/invoice-management/invoices/${invoice.id}/pdf`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf',
        },
      });
      
      if (!response.ok) {
        throw new Error('Failed to generate PDF');
      }
      
      // Create blob from response
      const blob = await response.blob();
      
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `invoice-${invoice.invoice_number || invoice.id}.pdf`;
      document.body.appendChild(link);
      link.click();
      
      // Cleanup
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      toastAction.current.showToast('PDF downloaded successfully!', 'success');
      setIsLoading(false);
    } catch (error) {
      console.error('PDF download failed:', error);
      toastAction.current.showToast('Failed to download PDF. Please try again.', 'error');
      setIsLoading(false);
    }
  };

  // Handle send email
  const handleSendEmail = () => {
    if (!invoice.id) return;
    
    if (window.confirm('Are you sure you want to send this invoice via email?\n\nThe invoice PDF will be attached to the email.')) {
      setIsLoading(true);
      toastAction.current.showToast('Sending email...', 'info');
      
      axiosClient.post(`/invoice-management/invoices/${invoice.id}/send-email`)
        .then((response) => {
          toastAction.current.showToast('Invoice has been sent via email successfully!', 'success');
          setIsLoading(false);
          console.log('Email sent successfully:', response.data);
        })
        .catch((errors) => {
          console.error('Email sending failed:', errors);
          setIsLoading(false);
          if (errors.response?.data?.message) {
            toastAction.current.showToast(`Email failed: ${errors.response.data.message}`, 'error');
          } else {
            toastAction.current.showToast('Failed to send email. Please try again.', 'error');
          }
        });
    }
  };

  // Handle file selection for current product in modal
  const handleCurrentProductFileSelect = (event) => {
    const files = Array.from(event.target.files);
    setCurrentProductFiles(files);
    
    // Create preview URLs for display
    const previewUrls = files.map(file => URL.createObjectURL(file));
    updateCurrentProductImages(previewUrls);
  };

  // Handle remove individual image for current product in modal
  const handleRemoveCurrentProductImage = (imageIndexToRemove) => {
    const updatedImages = currentProduct.product_images.filter((_, index) => index !== imageIndexToRemove);
    const updatedFiles = currentProductFiles.filter((_, index) => index !== imageIndexToRemove);
    updateCurrentProductImages(updatedImages);
    setCurrentProductFiles(updatedFiles);
  };

  // Handle remove all images for current product in modal
  const handleRemoveAllCurrentProductImages = () => {
    updateCurrentProductImages([]);
    setCurrentProductFiles([]);
  };

  return (
    <>
    <div className="card">
      <form onSubmit={onSubmit}>
        <div className="card-header">
          <h4>
            {invoice.id ? 'Edit Invoice' : 'Create New Invoice'}
          </h4>
          {!invoice.id && <p className="tip-message">Create a new invoice for your jewelry business.</p>}
        </div>
        <div className="card-body">
          {/* Invoice Number Field */}
          <Field
            label="Invoice Number"
            inputComponent={
              <input
                className="form-control"
                type="text"
                value={invoice.invoice_number || ''}
                onChange={ev => setInvoice({ ...invoice, invoice_number: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Auto-generated if left empty"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Customer Field */}
          <Field
            label="Customer"
            required={true}
            inputComponent={
              <select
                className="form-select"
                value={invoice.customer_id || ''}
                onChange={ev => setInvoice({ ...invoice, customer_id: ev.target.value })}
                required
              >
                <option value="">Select Customer</option>
                {customers.map(customer => (
                  <option key={customer.id} value={customer.id}>
                    {`${customer.first_name} ${customer.last_name}`} ({customer.email})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Issue Date Field */}
          <Field
            label="Issue Date"
            required={true}
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={invoice.issue_date || ''}
                onChange={ev => setInvoice({ ...invoice, issue_date: ev.target.value })}
                required
                placeholder="YYYY-MM-DD"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Due Date Field */}
          <Field
            label="Due Date"
            inputComponent={
              <input
                className="form-control"
                type="date"
                value={invoice.due_date || ''}
                onChange={ev => setInvoice({ ...invoice, due_date: ev.target.value })}
                placeholder="YYYY-MM-DD"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Status Field */}
          <Field
            label="Status"
            inputComponent={
              <select
                className="form-select"
                value={invoice.status || 'draft'}
                onChange={ev => setInvoice({ ...invoice, status: ev.target.value })}
              >
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="cancelled">Cancelled</option>
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Products Summary Section */}
          <div className="row mb-4">
            <div className="col-3">Products ({invoice.products.length})</div>
            <div className="col-9">
              <div className="d-flex justify-content-end align-items-center mb-3">
                <button 
                  type="button" 
                  className="btn btn-primary btn-sm"
                  onClick={openAddProductModal}
                >
                  <FontAwesomeIcon icon={solidIconMap.plus} className="me-1" />
                  Add Product
                </button>
              </div>
              <table className="table table-striped">
                  <thead>
                    <tr>
                      <th className="text-start">Product Name</th>
                      <th className="text-start">Description</th>
                      <th className="text-start">Price</th>
                      <th className="text-start">Images</th>
                      <th className="text-start">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {invoice.products.map((product, productIndex) => (
                      <tr key={productIndex}>
                        <td className="text-start">
                          <strong>{product.product_name || 'Unnamed Product'}</strong>
                        </td>
                        <td className="text-start">
                          <small>
                            {product.description ? 
                              (product.description.length > 50 ? 
                                `${product.description.substring(0, 50)}...` : 
                                product.description
                              ) : 
                              'No description'
                            }
                          </small>
                        </td>
                        <td className="text-start">
                          <strong>₱{parseFloat(product.price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                        </td>
                        <td className="text-start">
                          <div className="d-flex flex-wrap gap-1">
                            {product.product_images && product.product_images.length > 0 ? (
                              <>
                                {product.product_images.slice(0, 3).map((imageUrl, index) => (
                                  <img 
                                    key={index}
                                    src={imageUrl} 
                                    alt={`Product ${productIndex + 1}`}
                                    style={{ 
                                      width: '30px', 
                                      height: '30px', 
                                      objectFit: 'cover',
                                      borderRadius: '4px'
                                    }}
                                    className="border"
                                  />
                                ))}
                                {product.product_images.length > 3 && (
                                  <span className="badge bg-secondary">
                                    +{product.product_images.length - 3}
                                  </span>
                                )}
                              </>
                            ) : (
                              <span className="small">No images</span>
                            )}
                          </div>
                        </td>
                        <td className="text-start">
                          <div className="btn-group btn-group-sm">
                            <button 
                              type="button" 
                              className="btn btn-outline-primary"
                              onClick={() => openEditProductModal(productIndex)}
                              title="Edit Product"
                            >
                              <FontAwesomeIcon icon={solidIconMap.edit} />
                            </button>
                            {invoice.products.length > 1 && (
                              <button 
                                type="button" 
                                className="btn btn-outline-danger"
                                onClick={() => removeProduct(productIndex)}
                                title="Remove Product"
                              >
                                <FontAwesomeIcon icon={solidIconMap.trash} />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colSpan="4" className="text-end">
                        <strong>Subtotal:</strong>
                      </td>
                      <td className="text-start">
                        <strong>₱{Number(calculateSubtotal() || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                      </td>
                    </tr>
                    {invoice.tax_id && (
                      <tr>
                        <td colSpan="4" className="text-end">
                          <small>
                            Tax ({taxes.find(tax => tax.id == invoice.tax_id)?.name || 'Tax'}):
                          </small>
                        </td>
                        <td className="text-start">
                          <small>₱{Number(calculateTaxAmount() || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>
                        </td>
                      </tr>
                    )}
                    {invoice.fee_id && (
                      <tr>
                        <td colSpan="4" className="text-end">
                          <small>
                            Fee ({fees.find(fee => fee.id == invoice.fee_id)?.name || 'Fee'}):
                          </small>
                        </td>
                        <td className="text-start">
                          <small>₱{Number(calculateFeeAmount() || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>
                        </td>
                      </tr>
                    )}
                    {invoice.discount_id && (
                      <tr>
                        <td colSpan="4" className="text-end">
                          <small>
                            Discount ({discounts.find(discount => discount.id == invoice.discount_id)?.name || 'Discount'}):
                          </small>
                        </td>
                        <td className="text-start">
                          <small className="text-success">-₱{Number(calculateDiscountAmount() || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>
                        </td>
                      </tr>
                    )}
                    <tr className="border-top">
                      <td colSpan="4" className="text-end">
                        <strong>Total Amount:</strong>
                      </td>
                      <td className="text-start">
                        <strong className="fs-5">
                          ₱{Number(calculateTotal() || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </strong>
                      </td>
                    </tr>
                  </tfoot>
                </table>
            </div>
          </div>

          {/* Payment Term Field */}
          <Field
            label="Payment Term"
            inputComponent={
              <select
                className="form-select"
                value={invoice.payment_term_id || ''}
                onChange={ev => setInvoice({ ...invoice, payment_term_id: ev.target.value })}
              >
                <option value="">Select Payment Term</option>
                {paymentTerms.map(term => (
                  <option key={term.id} value={term.id}>
                    {term.name} ({term.code || term.id})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Payment Breakdown Display */}
          {invoice.payment_term_id && (
            <div className="row mb-4">
              <div className="col-3">Payment Breakdown</div>
              <div className="col-9">
                <div className="card">
                  <div className="card-body">
                    {(() => {
                      const selectedTerm = paymentTerms.find(term => term.id == invoice.payment_term_id);
                      const totalAmount = calculateTotal();
                      
                      if (!selectedTerm) return null;
                      
                      const downPaymentAmount = (totalAmount * (selectedTerm.down_payment_percentage || 0)) / 100;
                      const remainingAmount = totalAmount - downPaymentAmount;
                      
                      return (
                        <div className="row">
                          <div className="col-md-6">
                            <h6 className="text-primary">Down Payment</h6>
                            <div className="d-flex justify-content-between">
                              <span>Amount:</span>
                              <strong>₱{downPaymentAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                            </div>
                            <div className="d-flex justify-content-between">
                              <span>Percentage:</span>
                              <span>{selectedTerm.down_payment_percentage || 0}%</span>
                            </div>
                            <div className="d-flex justify-content-between">
                              <span>Due Date:</span>
                              <span>{invoice.issue_date || 'Issue Date'}</span>
                            </div>
                          </div>
                          <div className="col-md-6">
                            <h6 className="text-primary">Remaining Balance</h6>
                            <div className="d-flex justify-content-between">
                              <span>Amount:</span>
                              <strong>₱{remainingAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                            </div>
                            <div className="d-flex justify-content-between">
                              <span>Percentage:</span>
                              <span>{selectedTerm.remaining_percentage || 0}%</span>
                            </div>
                            <div className="d-flex justify-content-between">
                              <span>Term:</span>
                              <span>{selectedTerm.term_months || 0} months</span>
                            </div>
                          </div>
                        </div>
                      );
                    })()}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Monthly Payment Breakdown Display */}
          {invoice.payment_term_id && (() => {
            const selectedTerm = paymentTerms.find(term => term.id == invoice.payment_term_id);
            if (!selectedTerm) return null;
            
            const totalAmount = calculateTotal();
            const remainingAmount = totalAmount - ((totalAmount * (selectedTerm.down_payment_percentage || 0)) / 100);
            
            // If no schedules, show a simple message
            if (!selectedTerm.schedules || selectedTerm.schedules.length === 0) {
              return (
                <div className="row mb-4">
                  <div className="col-3">Monthly Payment Schedule</div>
                  <div className="col-9">
                    <div className="card">
                      <div className="card-body">
                        <div className="alert alert-warning border-warning">
                          <div className="d-flex align-items-center">
                            <FontAwesomeIcon icon={solidIconMap.exclamationTriangle} className="me-3 text-warning" style={{ fontSize: '1.5rem' }} />
                            <div>
                              <h6 className="alert-heading mb-2 text-dark">No Payment Schedule Defined</h6>
                              <p className="mb-2 text-dark">
                                This payment term does not have a detailed payment schedule configured.
                              </p>
                              <p className="mb-0 text-dark">
                                The remaining balance of <strong className="text-primary">₱{remainingAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong> will be due according to the payment term settings.
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              );
            }
            
            return (
              <div className="row mb-4">
                <div className="col-3">Monthly Payment Schedule</div>
                <div className="col-9">
                  <table className="table table-sm table-striped">
                    <thead>
                      <tr>
                        <th>Month</th>
                        <th>Description</th>
                        <th>Percentage</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedTerm.schedules.map((schedule, index) => {
                        const scheduleAmount = (remainingAmount * (schedule.percentage || 0)) / 100;
                        const dueDate = new Date(invoice.issue_date || new Date());
                        dueDate.setMonth(dueDate.getMonth() + (schedule.month_number || 0));
                        
                        return (
                          <tr key={index}>
                            <td>{schedule.month_number || 0}</td>
                            <td>{schedule.description || `Month ${schedule.month_number || 0}`}</td>
                            <td>{schedule.percentage || 0}%</td>
                             <td><strong>₱{scheduleAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
                            <td>{dueDate.toLocaleDateString()}</td>
                          </tr>
                        );
                      })}
                    </tbody>
                    <tfoot>
                      <tr className="table-primary">
                        <td colSpan="3"><strong>Total Remaining:</strong></td>
                         <td><strong>₱{remainingAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
                        <td></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            );
          })()}

          {/* Tax Field */}
          <Field
            label="Tax"
            inputComponent={
              <select
                className="form-select"
                value={invoice.tax_id || ''}
                onChange={ev => setInvoice({ ...invoice, tax_id: ev.target.value })}
              >
                <option value="">Select Tax</option>
                {taxes.map(tax => (
                  <option key={tax.id} value={tax.id}>
                    {tax.name} ({tax.percentage || tax.rate}%)
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Fee Field */}
          <Field
            label="Fee"
            inputComponent={
              <select
                className="form-select"
                value={invoice.fee_id || ''}
                onChange={ev => setInvoice({ ...invoice, fee_id: ev.target.value })}
              >
                <option value="">Select Fee</option>
                {fees.map(fee => (
                  <option key={fee.id} value={fee.id}>
                    {fee.name} (₱{fee.amount || fee.fee_amount})
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Discount Field */}
          <Field
            label="Discount"
            inputComponent={
              <select
                className="form-select"
                value={invoice.discount_id || ''}
                onChange={ev => setInvoice({ ...invoice, discount_id: ev.target.value })}
              >
                <option value="">Select Discount</option>
                {discounts.map(discount => (
                  <option key={discount.id} value={discount.id}>
                    {discount.name} ({discount.percentage || discount.discount_percentage}%)
                  </option>
                ))}
              </select>
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Shipping Address Field */}
          <Field
            label="Shipping Address"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={invoice.shipping_address || ''}
                onChange={ev => setInvoice({ ...invoice, shipping_address: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Customer shipping address"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

          {/* Notes Field */}
          <Field
            label="Notes"
            inputComponent={
              <textarea
                className="form-control"
                rows="3"
                value={invoice.notes || ''}
                onChange={ev => setInvoice({ ...invoice, notes: DOMPurify.sanitize(ev.target.value) })}
                placeholder="Additional notes or comments"
              />
            }
            labelClass="col-sm-12 col-md-3"
            inputClass="col-sm-12 col-md-9"
          />

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
          />
        </div>
        <div className="card-footer d-flex justify-content-between">
          <div>
            <Link type="button" to="/invoice-management/invoices" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.arrowleft} className="me-2" />
              Cancel
            </Link> &nbsp;
            <button type="submit" className="btn btn-secondary">
              <FontAwesomeIcon icon={solidIconMap.save} className="me-2" />
              {buttonText} &nbsp;
              {isLoading && <span className="spinner-border spinner-border-sm ml-1" role="status"></span>}
            </button>
          </div>
          <div>
            {invoice.id && (
              <>
                <button 
                  type="button" 
                  className="btn btn-primary me-2" 
                  onClick={handleDownloadPdf}
                  disabled={isLoading}
                >
                  <FontAwesomeIcon icon={solidIconMap.file} className="me-2" />
                  Download PDF
                </button>
                <button 
                  type="button" 
                  className="btn btn-success me-2" 
                  onClick={handleSendEmail}
                  disabled={isLoading}
                >
                  <FontAwesomeIcon icon={solidIconMap.envelope} className="me-2" />
                  Send Email
                </button>
              </>
            )}
            {invoice.id && invoice.status !== 'cancelled' && (
              <button 
                type="button" 
                className="btn btn-warning me-2" 
                onClick={handleCancel}
                disabled={isLoading}
              >
                <FontAwesomeIcon icon={solidIconMap.times} className="me-2" />
                Cancel Invoice
              </button>
            )}
            {invoice.id && (
              <button 
                type="button" 
                className="btn btn-danger" 
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
    
    {/* Product Modal */}
    {showProductModal && (
      <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
        <div className="modal-dialog modal-lg">
          <div className="modal-content">
            <div className="modal-header">
              <h5 className="modal-title">
                {editingProductIndex !== null ? 'Edit Product' : 'Add New Product'}
              </h5>
              <button 
                type="button" 
                className="btn-close" 
                onClick={() => setShowProductModal(false)}
              ></button>
            </div>
            <div className="modal-body p-0 m-0">
              <div className="card-body">
              {/* Product Name Field */}
              <Field
                label="Product Name"
                required={true}
                inputComponent={
                  <input
                    className="form-control"
                    type="text"
                    value={currentProduct.product_name || ''}
                    onChange={ev => updateCurrentProduct('product_name', DOMPurify.sanitize(ev.target.value))}
                    required
                    placeholder="e.g., Diamond Ring, Gold Necklace"
                  />
                }
                labelClass="col-sm-12 col-md-3"
                inputClass="col-sm-12 col-md-9"
              />

              {/* Description Field */}
              <Field
                label="Description"
                inputComponent={
                  <textarea
                    className="form-control"
                    rows="3"
                    value={currentProduct.description || ''}
                    onChange={ev => updateCurrentProduct('description', DOMPurify.sanitize(ev.target.value))}
                    placeholder="Product description and details"
                  />
                }
                labelClass="col-sm-12 col-md-3"
                inputClass="col-sm-12 col-md-9"
              />

              {/* Price Field */}
              <Field
                label="Price"
                required={true}
                inputComponent={
                  <div className="input-group">
                    <span className="input-group-text">₱</span>
                    <input
                      className="form-control"
                      type="number"
                      step="0.01"
                      min="0"
                      value={currentProduct.price || ''}
                      onChange={ev => updateCurrentProduct('price', ev.target.value)}
                      required
                      placeholder="0.00"
                    />
                  </div>
                }
                labelClass="col-sm-12 col-md-3"
                inputClass="col-sm-12 col-md-9"
              />

              {/* Product Images Field */}
              <Field
                label="Product Images"
                inputComponent={
                  <div>
                    {currentProduct.product_images && currentProduct.product_images.length > 0 && (
                      <div className="mb-3">
                        <div className="d-flex flex-wrap gap-2 mb-2">
                          {currentProduct.product_images.map((imageUrl, index) => (
                            <div key={index} className="position-relative d-inline-block">
                              <img 
                                src={imageUrl} 
                                alt={`Product Image ${index + 1}`} 
                                style={{ maxWidth: '150px', maxHeight: '150px', objectFit: 'cover' }}
                                className="img-thumbnail"
                                onLoad={() => {
                                  console.log('Image loaded successfully:', imageUrl);
                                }}
                                onError={(e) => {
                                  console.error('Image failed to load:', imageUrl);
                                  console.error('Error details:', e);
                                  // Show a placeholder instead of hiding
                                  e.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik03NSA0MEM4NS41IDQwIDk0IDQ4LjUgOTQgNTlDOTQgNjkuNSA4NS41IDc4IDc1IDc4QzY0LjUgNzggNTYgNjkuNSA1NiA1OUM1NiA0OC41IDY0LjUgNDAgNzUgNDBaIiBmaWxsPSIjOUNBM0FGIi8+CjxwYXRoIGQ9Ik03NSA4MEM4NS41IDgwIDk0IDg4LjUgOTQgOTlDOTQgMTA5LjUgODUuNSAxMTggNzUgMTE4QzY0LjUgMTE4IDU2IDEwOS41IDU2IDk5QzU2IDg4LjUgNjQuNSA4MCA3NSA4MFoiIGZpbGw9IiM5Q0EzQUYiLz4KPC9zdmc+';
                                  e.target.alt = 'Image failed to load';
                                }}
                              />
                              <button 
                                type="button" 
                                className="btn btn-sm btn-danger position-absolute top-0 end-0"
                                style={{ transform: 'translate(50%, -50%)' }}
                                onClick={() => handleRemoveCurrentProductImage(index)}
                                title="Remove this image"
                              >
                                <FontAwesomeIcon icon={solidIconMap.times} />
                              </button>
                            </div>
                          ))}
                        </div>
                        <button 
                          type="button" 
                          className="btn btn-sm btn-outline-danger"
                          onClick={handleRemoveAllCurrentProductImages}
                        >
                          <FontAwesomeIcon icon={solidIconMap.trash} className="me-1" />
                          Remove All Images
                        </button>
                      </div>
                    )}
                    <div className="mb-3">
                      <input
                        type="file"
                        className="form-control"
                        multiple
                        accept="image/*"
                        onChange={handleCurrentProductFileSelect}
                        id="current-product-images"
                      />
                      <label htmlFor="current-product-images" className="form-label">
                        <small>
                          Select multiple images (JPG, PNG, GIF, WebP). Each image should be less than 2MB.
                          {currentProductFiles.length > 0 && (
                            <span className="text-info d-block mt-1">
                              {currentProductFiles.length} new file(s) selected. These will replace existing images.
                            </span>
                          )}
                        </small>
                      </label>
                    </div>
                  </div>
                }
                labelClass="col-sm-12 col-md-3"
                inputClass="col-sm-12 col-md-9"
              />
              </div>
            </div>
             <div className="modal-footer p-0 m-0">
               <div className="card-footer w-100 m-0 d-flex justify-content-end gap-2"> 
                 <button 
                   type="button" 
                   className="btn btn-secondary" 
                   onClick={() => setShowProductModal(false)}
                 >
                   Cancel
                 </button>
                 <button 
                   type="button" 
                   className="btn btn-primary" 
                   onClick={saveProduct}
                 >
                   {editingProductIndex !== null ? 'Update Product' : 'Add Product'}
                 </button>
               </div>
             </div>
          </div>
        </div>
      </div>
    )}
    
    <ToastMessage ref={toastAction} />
    </>
  );
}
