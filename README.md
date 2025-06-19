# Weather Chatbot

An intelligent weather chatbot built with Laravel that provides real-time weather information through natural conversation. The bot uses AI (OpenAI) to understand user queries and fetches current weather data from the Open-Meteo API.

## Features

-   🤖 **AI-Powered Conversations**: Natural language interaction using OpenAI GPT-4o-mini
-   🌤️ **Real-Time Weather Data**: Current weather conditions from Open-Meteo API
-   💬 **Conversation Memory**: Maintains chat history across sessions
-   🌍 **Global Coverage**: Weather data for cities worldwide
-   📝 **Session Management**: Multiple conversation sessions with history
-   🎯 **Context Awareness**: Remembers recently mentioned cities in conversation

## Requirements

-   **PHP 8.2** or higher
-   **Composer** for PHP dependencies
-   **Node.js 18+** and **npm** for frontend assets
-   **SQLite** database (default) or MySQL/PostgreSQL
-   **OpenAI API Key** for AI functionality

## Installation

### 1. Clone and Install Dependencies

```bash
# Clone the repository
git clone <repository-url> weather-chatbot
cd weather-chatbot

# Install PHP dependencies
composer install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database file
touch database/database.sqlite
```

### 3. Configure Environment Variables

Edit `.env` file and add your OpenAI API key:

```env
# Required: OpenAI API Configuration
OPENAI_API_KEY=your_openai_api_key_here

# Database (SQLite is default, no changes needed)
DB_CONNECTION=sqlite
```

### 4. Database Setup

```bash
# Run database migrations
php artisan migrate
```

## Usage

### Interactive Chat Mode (Default)

Start a conversation with the weather bot:

```bash
# Start chatbot in interactive mode
php artisan app:fetch-weather

# Or use the Makefile shortcut
make weather
```

This starts an interactive session where you can:

-   Ask about weather in any city
-   Get contextual responses that remember previous cities mentioned
-   Type `quit`, `exit`, or `bye` to end the conversation
-   Type `history` to view conversation history
-   Type `clear` to clear conversation history

### Example Conversation

```
🤖 Weather Chatbot (Session: default)
Type your weather questions. Type 'quit', 'exit', or 'bye' to end the conversation.

You: What's the weather like in Paris?
🤔 Thinking...

🤖 The current weather in Paris is partly cloudy with a temperature of 18°C (64°F).
    The humidity is at 65% and there's a light wind of 12 km/h from the northwest.

You: How about tomorrow?
🤔 Thinking...

🤖 I can only provide current weather conditions. For tomorrow's forecast in Paris,
    you might want to check a dedicated weather forecasting service.

You: What about London?
🤔 Thinking...

🤖 In London right now, it's overcast with 15°C (59°F)...
```

### Session Management

```bash
# Use a specific session ID
php artisan app:fetch-weather --session=user123

# View conversation history
php artisan app:fetch-weather --history --session=user123

# Clear conversation history
php artisan app:fetch-weather --clear --session=user123
```

## Development

### Available Commands

```bash
# Start the weather chatbot
make weather

# Run tests
make test
php artisan test

# Code quality checks
make pint          # Code formatting
make stan          # Static analysis
make security      # Security audit

# Database operations
php artisan migrate:fresh    # Reset database
php artisan migrate         # Run migrations
```

### Testing

```bash
# Run all tests
make test
php artisan test

# Run specific test file
php artisan test tests/Feature/FetchWeatherCommandTest.php

# Run with coverage
php artisan test --coverage
```

## API Integration

### Weather Data Source

-   **Open-Meteo API**: Provides current weather conditions
-   **Geocoding**: Automatically converts city names to coordinates
-   **No API key required** for weather data

### AI Integration

-   **Prism PHP**: Laravel package for AI integration
-   **OpenAI GPT-4o-mini**: Powers natural language understanding
-   **Function calling**: AI can trigger weather data fetching when needed

## Architecture

### Core Services

-   **`OpenMeteoService`**: Handles weather API integration and geocoding
-   **`PrismService`**: Manages AI conversations and tool integration
-   **`FetchWeather`** Command: CLI interface for the chatbot
-   **`Conversation`** Model: Stores chat history with session management

### Database Schema

**Conversations Table:**

-   `session_id`: Groups messages by conversation session
-   `role`: Message type (system/user/assistant)
-   `content`: Message content
-   `metadata`: Additional data (JSON)
-   `timestamps`: Message timing

## Troubleshooting

### Common Issues

1. **Missing OpenAI API Key**

    ```
    Error: No API key provided
    Solution: Add OPENAI_API_KEY to your .env file
    ```

2. **Database Connection Issues**

    ```
    Error: Database file not found
    Solution: Ensure database/database.sqlite exists and run migrations
    ```

3. **Weather API Errors**
    ```
    Error: Could not find coordinates for city
    Solution: Check city name spelling or try a different city
    ```

### Getting Help

-   Check the logs: `storage/logs/laravel.log`
-   Run tests to verify setup: `php artisan test`
-   Ensure all environment variables are set correctly

## What I didn't build (on purpose):

-   No web interface - kept it CLI only for this assessment
-   No weather forecasts - just current conditions
-   No user accounts - simple session IDs instead
-   No caching - shows direct API integration
-   No auto location detection - users type city names

## Next steps:

-   Add caching (AI prompts + weather data) to reduce costs
-   Build a web interface
-   Add weather forecasts
-   Implement proper user auth
-   Add location detection via browser/IP

## Where to extend:

-   `app/Services/PrismService.php` - add prompt caching
-   `app/Services/OpenMeteoService.php` - add weather caching
-   `routes/web.php` - add web routes
-   `app/Http/Controllers/` - create web controllers
-   `resources/views/` - build frontend templates

## Design notes/caveats:

-   Can't detect user location (CLI app, no browser)
-   Used simple session IDs instead of real auth
-   No caching to keep it simple for assessment
-   Basic error handling only
