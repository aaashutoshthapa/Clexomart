#!/usr/bin/env python3

"""
ClexoMart IoT RFID Relay System

This Python script serves as a bridge between RFID hardware and the ClexoMart
Laravel application, enabling real-time inventory tracking through IoT integration.

Key Features:
- Serial communication with Arduino RFID readers
- Real-time data transmission to Laravel API
- Automatic error handling and recovery
- Continuous monitoring and logging
- JSON-based API communication

System Architecture:
1. Arduino RFID Reader → Serial Port → Python Relay → HTTP API → Laravel Backend
2. RFID tags are scanned and UIDs are captured
3. UIDs are immediately sent to Laravel for processing
4. Laravel updates inventory and product associations
5. Real-time inventory tracking for traders

Hardware Requirements:
- Arduino with RFID-RC522 module
- USB connection to computer running this script
- RFID tags attached to products

Software Requirements:
- Python 3.x with pyserial and requests libraries
- Laravel application running on localhost:8000
- Arduino sketch programmed to output RFID UIDs

Configuration:
- SERIAL_PORT: COM port where Arduino is connected
- BAUDRATE: Communication speed (must match Arduino)
- API_URL: Laravel API endpoint for RFID data

Usage:
1. Connect Arduino RFID reader to computer
2. Start Laravel application
3. Run this script: python relay.py
4. Scan RFID tags to update inventory

Error Handling:
- Serial connection failures
- API communication errors
- Data transmission timeouts
- Automatic retry mechanisms

@author ClexoMart Development Team
@version 1.0
@requires pyserial, requests
"""

import serial
import requests
import time

# ============================================================================
# CONFIGURATION SETTINGS
# Adjust these settings based on your hardware and server setup
# ============================================================================

SERIAL_PORT = 'COM6'                          # Arduino COM port (Windows)
                                               # Use '/dev/ttyUSB0' or '/dev/ttyACM0' on Linux
                                               # Use '/dev/cu.usbmodem*' on macOS

BAUDRATE    = 9600                             # Serial communication speed
                                               # Must match Arduino sketch configuration

API_URL     = 'http://127.0.0.1:8000/api/rfid'  # Laravel API endpoint
                                               # Adjust host/port if Laravel runs elsewhere

# ============================================================================
# MAIN RELAY FUNCTION
# Establishes serial connection and continuously processes RFID data
# ============================================================================

def main():
    """
    Main relay function that handles RFID data processing
    
    Process Flow:
    1. Initialize serial connection to Arduino
    2. Continuously read RFID UIDs from serial port
    3. Send each UID to Laravel API via HTTP POST
    4. Handle responses and errors gracefully
    5. Log all activities for debugging
    
    Error Recovery:
    - Continues operation despite individual read/send failures
    - Provides detailed error logging for troubleshooting
    - Maintains connection stability with timeout handling
    """
    
    # Serial Connection Initialization
    try:
        ser = serial.Serial(SERIAL_PORT, BAUDRATE, timeout=1)
        print(f"[+] RFID Relay Started: Listening on {SERIAL_PORT} at {BAUDRATE} baud")
        print(f"[+] API Endpoint: {API_URL}")
        print(f"[+] Ready to process RFID tags...")
    except Exception as e:
        print(f"[!] Serial Connection Failed: Could not open {SERIAL_PORT}: {e}")
        print(f"[!] Please check:")
        print(f"    - Arduino is connected to {SERIAL_PORT}")
        print(f"    - Arduino drivers are installed")
        print(f"    - Port is not in use by another application")
        return

    # Main Processing Loop
    while True:
        try:
            # RFID Data Reading: Get UID from Arduino via serial
            raw = ser.readline().decode('utf-8', errors='ignore').strip()
            
            # Skip empty reads (normal during idle periods)
            if not raw:
                continue
            
            # Data Processing: Clean and format UID
            uid = raw.upper()                   # Standardize to uppercase
            print(f"[+] RFID Tag Detected: {uid}")
            
            # API Communication: Send UID to Laravel backend
            resp = requests.post(
                API_URL,
                json={'uid': uid},              # Send UID as JSON payload
                headers={
                    'Accept': 'application/json',  # Request JSON response
                    'Content-Type': 'application/json'
                },
                timeout=2                       # 2-second timeout for API calls
            )
            
            # Response Processing: Handle API response
            if resp.ok:
                print(f"[+] Success: UID {uid} processed by Laravel")
                
                # Parse response for additional info if available
                try:
                    response_data = resp.json()
                    if 'message' in response_data:
                        print(f"[+] Server Response: {response_data['message']}")
                except:
                    pass  # Continue if JSON parsing fails
                    
            else:
                # Error Handling: Log API errors with details
                error_msg = (resp.text[:120] + '…') if resp.text else resp.reason
                print(f"[!] API Error {resp.status_code}: {error_msg}")
                print(f"[!] Failed to process UID: {uid}")
                
        except requests.exceptions.Timeout:
            print(f"[!] API Timeout: Laravel server not responding")
            print(f"[!] Check if Laravel application is running on {API_URL}")
            
        except requests.exceptions.ConnectionError:
            print(f"[!] Connection Error: Cannot reach Laravel API")
            print(f"[!] Verify Laravel server is running and accessible")
            
        except serial.SerialException as e:
            print(f"[!] Serial Error: {e}")
            print(f"[!] Check Arduino connection and port settings")
            
        except Exception as e:
            print(f"[!] Unexpected Error: {e}")
            
        # Rate Limiting: Small delay to prevent overwhelming the system
        time.sleep(0.1)

# ============================================================================
# SCRIPT ENTRY POINT
# ============================================================================

if __name__ == '__main__':
    print("=" * 60)
    print("ClexoMart IoT RFID Relay System")
    print("Real-time Inventory Tracking via RFID")
    print("=" * 60)
    
    try:
        main()
    except KeyboardInterrupt:
        print("\n[+] RFID Relay stopped by user (Ctrl+C)")
        print("[+] Goodbye!")
    except Exception as e:
        print(f"\n[!] Fatal Error: {e}")
        print("[!] RFID Relay terminated unexpectedly")
