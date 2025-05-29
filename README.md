# Dental Directory System - WordPress Plugin

A comprehensive WordPress plugin for creating a dental directory system with chat functionality, reviews, and subscription-based monetization.

## Features

- **Directory Listings:** Search and filter dentist profiles
- **Chat System:** Real-time communication between patients and dentists
- **Message Limits:** 5 message limit for free dentists, unlimited for premium
- **Reviews & Ratings:** Patients can leave reviews for dentists
- **Subscription System:** Monetization through premium subscriptions
- **Elementor Integration:** Custom widgets for seamless design
- **Custom User Roles:** Dedicated dentist and patient roles with specific capabilities
- **Existing User Integration:** Convert standard WordPress users to directory users

## Technical Overview

- Frontend-only system (no wp-admin usage for end users)
- Custom user roles: dentists and patients
- Custom database tables for efficient data management
- AJAX-based real-time chat and directory filtering
- WordPress REST API endpoints for frontend-backend communication
- Comprehensive permissions system with role-specific capabilities
- Email verification system for new user registration

## User Management Features

### User Roles
- **Dentist Role:** Custom capabilities for managing profiles, responding to messages, and handling reviews
- **Patient Role:** Special capabilities for sending messages, writing reviews, and browsing the directory

### User Registration
- Custom registration forms for dentists and patients
- Auto-assignment of free subscription to new dentist accounts
- Email verification system for account activation

### Existing WordPress Users
- Simple role selection process for existing users
- Seamless conversion of standard WordPress accounts to directory users
- Profile completion workflow for converted users

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Elementor (recommended for full functionality)

## Development Status

This plugin is currently in active development.

## License

GPL v2 or later
