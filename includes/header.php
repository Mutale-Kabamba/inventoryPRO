<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'LedgerLink Inventory'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f9fafb;
            min-height: 100vh;
            line-height: 1.5;
        }
        
        /* Navigation */
        .nav {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-bottom: 1px solid #e5e7eb;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            height: 64px;
            position: relative;
            justify-content: space-between;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            justify-content: center;
            min-width: 0;
            z-index: 1;
        }
        .nav-brand {
            position: static;
            left: unset;
            transform: none;
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
            letter-spacing: 0.5px;
            white-space: nowrap;
            z-index: 2;
            margin-right: 2rem;
            pointer-events: auto;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-left: auto;
            white-space: nowrap;
            z-index: 2;
        }
        .nav-link {
            text-decoration: none;
            color: #374151;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 0.7rem 0.3rem 0.6rem 0.3rem;
            border-bottom: 2px solid transparent;
            transition: color 0.2s, border-bottom 0.2s;
            background: none;
            white-space: nowrap;
        }
        .nav-link.active {
            color: #2563eb;
            border-bottom: 2.5px solid #2563eb;
            background: none;
        }
        .nav-link:hover {
            color: #2563eb;
            background: none;
        }
        .dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .dropdown-toggle {
            position: relative;
            padding-right: 1.2em;
        }
        .dropdown-toggle::after {
            content: '';
            font-size: 0.7em;
            margin-left: 0.3em;
            position: static;
            vertical-align: middle;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            border: 1px solid #ccc;
            min-width: 160px;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 0 0 6px 6px;
            margin-top: 0.2rem;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu a {
            display: block;
            padding: 10px 18px;
            color: #333;
            text-decoration: none;
            font-size: 0.95rem;
            transition: background 0.2s, color 0.2s;
        }
        .dropdown-menu a:hover {
            background: #2563eb;
            color: #fff;
        }
        .nav-user {
            font-size: 1rem;
            color: #374151;
            font-weight: 500;
            padding-right: 0.5rem;
            white-space: nowrap;
        }
        .nav-logout {
            text-decoration: none;
            font-size: 1rem;
            color: #6b7280;
            background: #f3f4f6;
            border-radius: 4px;
            padding: 6px 14px;
            transition: background 0.2s, color 0.2s;
            white-space: nowrap;
        }
        .nav-logout:hover {
            color: #fff;
            background: #2563eb;
        }
        @media (max-width: 900px) {
            .nav-links {
                gap: 1rem;
            }
            .nav-brand {
                font-size: 1.1rem;
            }
            .nav-link {
                font-size: 0.95rem;
                padding: 1rem 0.3rem 0.8rem 0.3rem;
            }
            .nav-right {
                gap: 0.7rem;
            }
        }
        @media (max-width: 700px) {
            .nav-container {
                flex-wrap: wrap;
                height: auto;
                padding: 0 0.5rem;
            }
            .nav-links {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .nav-brand {
                position: static;
                transform: none;
                margin: 0 auto;
                order: -1;
            }
            .nav-right {
                position: static;
                height: auto;
                margin-left: auto;
                margin-top: 0.5rem;
            }
        }
        
        /* Main content */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        /* Page header */
        .page-header {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: #6b7280;
        }
        
        /* Grid layouts */
        .grid-1 { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }
        
        .table tbody tr:hover {
            background: #f9fafb;
        }
        
        /* Forms */
        .form-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        .btn-success {
            background: #059669;
            color: white;
        }
        
        .btn-success:hover {
            background: #047857;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-outline {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-outline:hover {
            background: #f9fafb;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        
        /* Alerts */
        .alert {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            color: #d97706;
        }
        
        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .action-buttons .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .main-container {
                padding: 1rem;
            }
            
            .grid-2,
            .grid-3,
            .grid-4 {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
        
        /* Utility classes */
        .mb-4 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1rem; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-sm { font-size: 0.875rem; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-gray { color: #6b7280; }
        
        /* Enhanced Modal System */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: modalOverlayFadeIn 0.3s ease-out forwards;
        }
        
        @keyframes modalOverlayFadeIn {
            from { 
                opacity: 0; 
                backdrop-filter: blur(0px);
            }
            to { 
                opacity: 1; 
                backdrop-filter: blur(12px);
            }
        }
        
        @keyframes modalSlideIn {
            from { 
                opacity: 0; 
                transform: translateY(-30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 
                0 32px 64px -12px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            max-width: 520px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            position: relative;
        }
        
        .modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
        }
        
        .modal-header {
            padding: 32px 32px 24px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-30px, 30px);
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 2;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .modal-body {
            padding: 32px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            max-height: calc(90vh - 160px);
            overflow-y: auto;
        }
        
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 3px;
        }
        
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }
        
        .modal-footer {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        /* Enhanced Modal Form Styles */
        .modal-form .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .modal-form .form-group:last-child {
            margin-bottom: 0;
        }
        
        .modal-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }
        
        .modal-form label::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 1px;
        }
        
        .modal-form input,
        .modal-form select,
        .modal-form textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid rgba(102, 126, 234, 0.1);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            position: relative;
        }
        
        .modal-form input:hover,
        .modal-form select:hover,
        .modal-form textarea:hover {
            border-color: rgba(102, 126, 234, 0.3);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }
        
        .modal-form input:focus,
        .modal-form select:focus,
        .modal-form textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(102, 126, 234, 0.15),
                0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .modal-form input::placeholder,
        .modal-form textarea::placeholder {
            color: #9ca3af;
            font-style: italic;
        }
        
        .modal-form textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .modal-form select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .modal-form input[type="hidden"] {
            display: none;
        }
        
        /* Enhanced Modal Footer */
        .modal-footer {
            padding: 24px 32px;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(102, 126, 234, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 16px;
        }
        
        /* Enhanced Button Styles for Modals */
        .btn-modal {
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modal:hover::before {
            left: 100%;
        }
        
        .btn-modal-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-modal-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            filter: brightness(1.05);
        }
        
        .btn-modal-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-modal-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-modal-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            filter: brightness(1.05);
        }
        
        .btn-modal-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
        }
        
        .btn-modal-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
            filter: brightness(1.05);
        }
        
        .btn-modal-secondary {
            background: rgba(248, 250, 252, 0.9);
            color: #64748b;
            border: 2px solid rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .btn-modal-secondary:hover {
            background: rgba(241, 245, 249, 0.95);
            color: #475569;
            border-color: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .btn-modal:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-modal:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .btn-modal-danger {
            background-color: #dc2626;
            color: white;
        }
        
        .btn-modal-danger:hover {
            background-color: #b91c1c;
        }
        
        .btn-modal-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .btn-modal-secondary:hover {
            background-color: #4b5563;
        }
        
        .btn-modal-outline {
            background-color: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        
        .btn-modal-outline:hover {
            background-color: #f9fafb;
            color: #374151;
        }
        
        /* Confirmation Modal Styles */
        .confirmation-content {
            text-align: center;
            padding: 1rem 0;
        }
        
        .confirmation-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .confirmation-icon.danger {
            background-color: #fef2f2;
            color: #dc2626;
        }
        
        .confirmation-icon.warning {
            background-color: #fffbeb;
            color: #d97706;
        }
        
        .confirmation-message {
            font-size: 1.125rem;
            font-weight: 500;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .confirmation-description {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        /* Loading State */
        .btn-loading {
            opacity: 0.75;
            pointer-events: none;
            position: relative;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Modal */
        @media (max-width: 640px) {
            .modal {
                width: 95%;
                margin: 1rem;
                max-height: calc(100vh - 2rem);
            }
            
            .modal-header,
            .modal-body,
            .modal-footer {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .btn-modal {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    
    <!-- Modal JavaScript -->
    <script>
        // Modal System
        class ModalSystem {
            constructor() {
                this.init();
            }
            
            init() {
                // Create modal container if it doesn't exist
                if (!document.getElementById('modal-container')) {
                    const container = document.createElement('div');
                    container.id = 'modal-container';
                    document.body.appendChild(container);
                }
                
                // Close modal on overlay click
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('modal-overlay')) {
                        this.closeModal();
                    }
                });
                
                // Close modal on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.closeModal();
                    }
                });
            }
            
            showModal(content, options = {}) {
                const container = document.getElementById('modal-container');
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                overlay.innerHTML = content;
                
                container.innerHTML = '';
                container.appendChild(overlay);
                
                // Show modal with animation
                requestAnimationFrame(() => {
                    overlay.classList.add('active');
                });
                
                // Focus first input if exists
                setTimeout(() => {
                    const firstInput = overlay.querySelector('input, select, textarea');
                    if (firstInput) firstInput.focus();
                }, 200);
                
                return overlay;
            }
            
            closeModal() {
                const overlay = document.querySelector('.modal-overlay.active');
                if (overlay) {
                    overlay.classList.remove('active');
                    setTimeout(() => {
                        overlay.remove();
                    }, 200);
                }
            }
            
            confirm(message, description = '', options = {}) {
                return new Promise((resolve) => {
                    const content = `
                        <div class="modal">
                            <div class="modal-header">
                                <h3 class="modal-title">${options.title || 'Confirm Action'}</h3>
                                <button type="button" class="modal-close" onclick="modal.closeModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="confirmation-content">
                                    <div class="confirmation-icon ${options.type || 'warning'}">
                                        ${options.icon || '⚠️'}
                                    </div>
                                    <div class="confirmation-message">${message}</div>
                                    ${description ? `<div class="confirmation-description">${description}</div>` : ''}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-modal btn-modal-outline" onclick="modal.closeModal(); modal.confirmResolve(false)">
                                    ${options.cancelText || 'Cancel'}
                                </button>
                                <button type="button" class="btn-modal btn-modal-danger" onclick="modal.closeModal(); modal.confirmResolve(true)">
                                    ${options.confirmText || 'Confirm'}
                                </button>
                            </div>
                        </div>
                    `;
                    
                    this.confirmResolve = resolve;
                    this.showModal(content);
                });
            }
            
            showForm(title, fields, options = {}) {
                return new Promise((resolve) => {
                    let formFields = '';
                    fields.forEach(field => {
                        if (field.type === 'select') {
                            formFields += `
                                <div class="form-group">
                                    <label for="${field.name}">${field.label}</label>
                                    <select id="${field.name}" name="${field.name}" ${field.required ? 'required' : ''}>
                                        ${field.options.map(opt => 
                                            `<option value="${opt.value}" ${opt.selected ? 'selected' : ''}>${opt.text}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            `;
                        } else if (field.type === 'textarea') {
                            formFields += `
                                <div class="form-group">
                                    <label for="${field.name}">${field.label}</label>
                                    <textarea id="${field.name}" name="${field.name}" ${field.required ? 'required' : ''}>${field.value || ''}</textarea>
                                </div>
                            `;
                        } else {
                            formFields += `
                                <div class="form-group">
                                    <label for="${field.name}">${field.label}</label>
                                    <input type="${field.type || 'text'}" id="${field.name}" name="${field.name}" 
                                           value="${field.value || ''}" ${field.required ? 'required' : ''}
                                           ${field.placeholder ? `placeholder="${field.placeholder}"` : ''}>
                                </div>
                            `;
                        }
                    });
                    
                    const content = `
                        <div class="modal">
                            <div class="modal-header">
                                <h3 class="modal-title">${title}</h3>
                                <button type="button" class="modal-close" onclick="modal.closeModal()">&times;</button>
                            </div>
                            <form class="modal-form" onsubmit="modal.handleFormSubmit(event)">
                                <div class="modal-body">
                                    ${formFields}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn-modal btn-modal-outline" onclick="modal.closeModal()">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn-modal btn-modal-primary">
                                        ${options.submitText || 'Save'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    `;
                    
                    this.formResolve = resolve;
                    this.showModal(content);
                });
            }
            
            handleFormSubmit(event) {
                event.preventDefault();
                const formData = new FormData(event.target);
                const data = Object.fromEntries(formData.entries());
                this.closeModal();
                if (this.formResolve) {
                    this.formResolve(data);
                }
            }
            
            loading(message = 'Loading...') {
                const content = `
                    <div class="modal">
                        <div class="modal-body" style="text-align: center; padding: 2rem;">
                            <div style="margin-bottom: 1rem;">
                                <div style="width: 40px; height: 40px; border: 4px solid #f3f4f6; border-top: 4px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                            </div>
                            <div>${message}</div>
                        </div>
                    </div>
                `;
                return this.showModal(content);
            }
        }
        
        // Initialize modal system when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing modal system...');
            window.modal = new ModalSystem();
            console.log('Modal system initialized:', window.modal);
        });
        
        // Helper functions for CRUD operations
        window.openAddModal = function(type, fields, submitUrl) {
            if (!window.modal) {
                console.error('Modal system not initialized');
                return;
            }
            window.modal.showForm(`Add New ${type}`, fields, { submitText: 'Add' }).then(data => {
                window.submitForm(submitUrl, data, 'POST');
            });
        }
        
        window.openEditModal = function(type, fields, submitUrl, id) {
            if (!window.modal) {
                console.error('Modal system not initialized');
                return;
            }
            window.modal.showForm(`Edit ${type}`, fields, { submitText: 'Update' }).then(data => {
                window.submitForm(submitUrl, data, 'POST', id);
            });
        }
        
        window.openDeleteModal = function(type, name, deleteUrl, id) {
            if (!window.modal) {
                console.error('Modal system not initialized');
                return;
            }
            window.modal.confirm(
                `Delete ${type}?`,
                `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                { 
                    title: 'Confirm Delete',
                    confirmText: 'Delete',
                    type: 'danger',
                    icon: '🗑️'
                }
            ).then(confirmed => {
                if (confirmed) {
                    window.submitForm(deleteUrl, { id: id }, 'POST');
                }
            });
        }
        
        window.submitForm = function(url, data, method = 'POST', id = null) {
            if (!window.modal) {
                console.error('Modal system not initialized');
                return;
            }
            const loadingModal = window.modal.loading('Processing...');
            
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
            if (id) formData.append('id', id);
            
            fetch(url, {
                method: method,
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                window.modal.closeModal();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'An error occurred');
                }
            })
            .catch(error => {
                window.modal.closeModal();
                alert('An error occurred: ' + error.message);
            });
        }
        
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Modal system initializing...');
            
            // Auto-bind modal triggers
            document.addEventListener('click', function(e) {
                console.log('Click detected on:', e.target);
                
                if (e.target.matches('[data-modal-add]')) {
                    e.preventDefault();
                    console.log('Add modal triggered');
                    try {
                        const config = JSON.parse(e.target.getAttribute('data-modal-add'));
                        console.log('Config parsed:', config);
                        window.openAddModal(config.type, config.fields, config.url);
                    } catch (error) {
                        console.error('Error parsing modal config:', error);
                        alert('Error opening modal: ' + error.message);
                    }
                }
                
                if (e.target.matches('[data-modal-edit]')) {
                    e.preventDefault();
                    console.log('Edit modal triggered');
                    try {
                        const config = JSON.parse(e.target.getAttribute('data-modal-edit'));
                        window.openEditModal(config.type, config.fields, config.url, config.id);
                    } catch (error) {
                        console.error('Error parsing modal config:', error);
                        alert('Error opening modal: ' + error.message);
                    }
                }
                
                if (e.target.matches('[data-modal-delete]')) {
                    e.preventDefault();
                    console.log('Delete modal triggered');
                    try {
                        const config = JSON.parse(e.target.getAttribute('data-modal-delete'));
                        window.openDeleteModal(config.type, config.name, config.url, config.id);
                    } catch (error) {
                        console.error('Error parsing modal config:', error);
                        alert('Error opening modal: ' + error.message);
                    }
                }
            });
        });
    </script>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="nav">
    <div class="nav-container">
        <a href="../public/dashboard.php" class="nav-brand">LedgerLink</a>
        <div class="nav-links">
            <a href="../users/list.php" class="nav-link <?php echo ($currentPage ?? '') == 'admin' ? 'active' : ''; ?>">ADMIN</a>
            <a href="../public/dashboard.php" class="nav-link <?php echo ($currentPage ?? '') == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="../products/list.php" class="nav-link <?php echo ($currentPage ?? '') == 'products' ? 'active' : ''; ?>">Products</a>
            <a href="../sales/list.php" class="nav-link <?php echo ($currentPage ?? '') == 'sales' ? 'active' : ''; ?>">Sales</a>
            <a href="../purchases/list.php" class="nav-link <?php echo ($currentPage ?? '') == 'purchases' ? 'active' : ''; ?>">Purchases</a>
            <a href="../reports/daily.php" class="nav-link <?php echo ($currentPage ?? '') == 'reports' ? 'active' : ''; ?>">Report</a>
            
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" onclick="toggleDropdown(event)">More ▼</a>
                <div class="dropdown-menu" id="moreDropdown">
                    <a href="../rentals/record.php">Rentals</a>
                    <!-- Future features go here -->
                </div>
            </div>
        </div>
        <div class="nav-right">
            <?php if (isset($_SESSION['username'])): ?>
                <span class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../public/logout.php" class="nav-logout">Sign Out</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

    <!-- Main Content -->
    <div class="main-container">