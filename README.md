# OnlineShop - Real-World Ecommerce Platform

A modern, professional ecommerce platform built with PHP, Bootstrap 5, and real-world UI/UX patterns.

## Features

### Customer Experience
- **Modern Homepage** with hero section, featured products, and category browsing
- **Product Catalog** with advanced filtering and search
- **User Accounts** with profile management and order history
- **Shopping Cart** with real-time updates
- **Responsive Design** that works on all devices

### Admin Dashboard
- **Analytics Dashboard** with sales charts and key metrics
- **Product Management** with CRUD operations
- **Order Management** with status tracking
- **User Management** with role-based access
- **Category Management** with hierarchical structure
- **Reports & Analytics** with export functionality
- **System Settings** for payment, shipping, and security

### Design Highlights
- **Modern UI** with Manrope font family
- **Professional Color Scheme** with gradients and shadows
- **Smooth Animations** and micro-interactions
- **Admin Sidebar** with intuitive navigation
- **Data Tables** with sorting and filtering
- **Modal Forms** for adding/editing content
- **Status Badges** and progress indicators
- **Responsive Grid** layouts

## File Structure

```
onlineshop/
├── admin.php                 # Admin dashboard
├── admin_products.php        # Product management
├── admin_orders.php          # Order management  
├── admin_users.php           # User management
├── admin_categories.php      # Category management
├── admin_reports.php         # Reports and analytics
├── admin_settings.php        # System settings
├── index.php                 # Homepage
├── products.php              # Product catalog
├── my-account.php            # User account
├── my-orders.php             # Order history
├── includes/
│   ├── admin_header.php      # Admin header/navigation
│   ├── admin_footer.php      # Admin footer
│   ├── header.php            # Customer header
│   ├── footer.php            # Customer footer
│   ├── auth.php              # Authentication functions
│   ├── config.php            # Configuration
│   └── db.php                # Database connection
├── assets/css/
│   └── style.css             # Main stylesheet
└── assets/images/            # Product images
```

## Getting Started

1. **Setup Database**
   - Import the database schema
   - Configure database credentials in `includes/config.php`

2. **Admin Access**
   - Create an admin user with `role = 'admin'` in the users table
   - Access admin panel at `/admin.php`

3. **Customization**
   - Update site name and settings in `includes/config.php`
   - Modify colors and fonts in `assets/css/style.css`
   - Add your own product images

## Key Features Implemented

### Real-World UI Elements
- **Professional Navigation** with search bar and user menu
- **Hero Section** with video background
- **Product Cards** with hover effects
- **Category Grid** with image overlays
- **Testimonials** with star ratings
- **Newsletter Signup** with gradient background

### Admin Dashboard
- **Sidebar Navigation** with icons and active states
- **Statistics Cards** with trend indicators
- **Data Tables** with actions and status badges
- **Modal Forms** for adding/editing
- **Charts** using Chart.js
- **Settings Pages** with toggle switches

### Modern CSS Features
- **CSS Grid** and Flexbox layouts
- **Custom Properties** for consistent theming
- **Smooth Transitions** and hover effects
- **Responsive Design** with mobile-first approach
- **Professional Typography** with Google Fonts
- **Box Shadows** and gradients for depth

## Security Features
- **Role-Based Access Control** for admin pages
- **Session Management** with timeout
- **Input Validation** and sanitization
- **Password Hashing** with PHP's password_hash()
- **CSRF Protection** ready forms

## Payment Integration Ready
- **Multiple Payment Methods** (PayPal, Stripe, KPay, AyaPay)
- **Cash on Delivery** option
- **Currency Settings** for international stores
- **Shipping Calculation** based on order total

## Mobile Responsive
- **Mobile-First Design** approach
- **Collapsible Navigation** for small screens
- **Touch-Friendly** buttons and forms
- **Optimized Tables** for mobile viewing
- **Responsive Grid** layouts

This ecommerce platform provides a complete, professional solution with modern UI/UX patterns that you'd expect from real-world applications like Shopify, Amazon, or other major ecommerce platforms.
