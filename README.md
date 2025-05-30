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
- Database migration system for schema versioning and updates

## User Management Features

### User Roles
- **Dentist Role:** Custom capabilities for managing profiles, responding to messages, and handling reviews
- **Patient Role:** Special capabilities for sending messages, writing reviews, and browsing the directory

### User Registration
- Custom registration forms for dentists and patients
- Auto-assignment of free subscription to new dentist accounts
- Email verification system for account activation with secure tokens
- Resend verification option for improved user experience
- Advanced password strength validation
- Robust form validation with real-time feedback
- Secure AJAX processing with nonce verification

### Existing WordPress Users
- Simple role selection process for existing users
- Seamless conversion of standard WordPress accounts to directory users
- Profile completion workflow for converted users

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Elementor (recommended for full functionality)

## Database Schema

The plugin uses a custom database schema with optimized tables for performance and functionality:

### Core Tables

- **wp_dental_profiles** - Comprehensive dentist profile information including specialties, location, services
- **wp_dental_chat_messages** - Individual messages between dentists and patients 
- **wp_dental_conversations** - Groups messages into conversations between a dentist and patient
- **wp_dental_subscriptions** - Manages dentist subscription plans and status
- **wp_dental_reviews** - Stores patient reviews of dentists with ratings and responses

### Supporting Tables

- **wp_dental_message_counters** - Critical business logic for tracking free tier message limits
- **wp_dental_subscription_payments** - Payment history for subscription transactions
- **wp_dental_review_votes** - Tracks helpful/unhelpful votes on reviews
- **wp_dental_favorites** - Records which dentists a patient has favorited

### Database Features

- **Migration System** - Versioned database schema with safe upgrades and rollbacks
- **Transaction Safety** - All schema changes happen within database transactions
- **Optimized Indexes** - Carefully designed indexes for query performance
- **Foreign Keys** - Maintains data integrity across related tables
- **Expandable Design** - Schema can be extended with minimal impact to existing data

For the complete schema documentation, see `/includes/database/schema-documentation.md`

## Integración con WooCommerce

El plugin se integra con WooCommerce para gestionar las suscripciones premium. A continuación se detallan los pasos para configurar los productos de suscripción necesarios.

### Requisitos previos

- WooCommerce instalado y activado
- Plugin WooCommerce Subscriptions instalado y activado

### Configuración de productos de suscripción

#### 1. Suscripción Premium Mensual

1. **Crear nuevo producto en WooCommerce**:
   - Ve a WooCommerce > Productos > Añadir nuevo

2. **Información básica**:
   - **Nombre**: "Suscripción Premium Mensual para Dentistas"
   - **Descripción**: "Mensajes ilimitados y perfil destacado por un mes. Renovación automática mensual."
   - **Precio regular**: Establece el precio mensual (por ejemplo, $19.99)

3. **Tipo de producto**:
   - En la sección "Datos del producto", selecciona "Suscripción" en el menú desplegable

4. **Configuración de suscripción**:
   - **Periodo de suscripción**: Selecciona "Mes"
   - **Intervalo de suscripción**: 1
   - **Duración de la suscripción**: Selecciona "Cobrar indefinidamente hasta cancelación"

5. **Configuración clave para la integración**:
   - **SKU**: `dental-premium-monthly` (**OBLIGATORIO** - El plugin busca este SKU específico)
   - En "Meta personalizada", añade `_dental_subscription_type` con valor `monthly`

6. Haz clic en "Publicar" para guardar el producto

#### 2. Suscripción Premium Anual

1. **Crear nuevo producto en WooCommerce**:
   - Ve a WooCommerce > Productos > Añadir nuevo

2. **Información básica**:
   - **Nombre**: "Suscripción Premium Anual para Dentistas"
   - **Descripción**: "Mensajes ilimitados y perfil destacado por un año completo. Ahorra con respecto al plan mensual."
   - **Precio regular**: Establece el precio anual (por ejemplo, $199.99)

3. **Tipo de producto**:
   - En la sección "Datos del producto", selecciona "Suscripción" en el menú desplegable

4. **Configuración de suscripción**:
   - **Periodo de suscripción**: Selecciona "Año"
   - **Intervalo de suscripción**: 1
   - **Duración de la suscripción**: Selecciona "Cobrar indefinidamente hasta cancelación"

5. **Configuración clave para la integración**:
   - **SKU**: `dental-premium-yearly` (**OBLIGATORIO** - El plugin busca este SKU específico)
   - En "Meta personalizada", añade `_dental_subscription_type` con valor `yearly`

6. Haz clic en "Publicar" para guardar el producto

### Flujo de suscripción

1. **Plan gratuito**: Los dentistas comienzan con un plan gratuito que permite 5 mensajes/mes
2. **Límite alcanzado**: Cuando un dentista alcanza el límite, se le muestra un botón para actualizar
3. **Proceso de actualización**:
   - El plugin redirige al usuario al checkout de WooCommerce con el producto seleccionado
   - WooCommerce gestiona el proceso de pago y creación de suscripción
   - Al completarse el pago, el plugin actualiza automáticamente el estado del usuario a premium

### Configuración alternativa (mediante IDs)

Si prefieres no usar los SKUs predeterminados, puedes especificar los IDs de los productos directamente:

1. Navega a Dental Directory > Configuración > Suscripciones
2. Introduce los IDs de tus productos de suscripción
3. Guarda la configuración

### Solución de problemas

- Si las suscripciones no funcionan correctamente, verifica que los SKUs están configurados exactamente como `dental-premium-monthly` y `dental-premium-yearly`
- Asegúrate de que WooCommerce Subscriptions está activado y configurado correctamente
- Verifica que los productos sean de tipo "Suscripción" y no "Simple" u otro tipo

## Development Status

This plugin is currently in active development.

## License

GPL v2 or later
