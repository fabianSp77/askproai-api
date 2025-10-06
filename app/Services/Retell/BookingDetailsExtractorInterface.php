<?php

namespace App\Services\Retell;

use App\Models\Call;
use Carbon\Carbon;

/**
 * Booking Details Extractor Interface
 *
 * Centralized contract for extracting appointment booking details
 * from Retell AI calls and transcripts
 *
 * RESPONSIBILITY: Extract structured booking information from unstructured
 * call data, transcripts, and Retell analysis data
 *
 * SCOPE:
 * - Retell custom_analysis_data parsing
 * - German language transcript parsing
 * - Date/time extraction with multiple formats
 * - Service name extraction
 * - Confidence calculation
 * - Data validation
 *
 * OUT OF SCOPE (separate service):
 * - Appointment creation logic (AppointmentCreationService)
 * - Customer management (AppointmentCreationService)
 * - Cal.com integration (CalcomService)
 */
interface BookingDetailsExtractorInterface
{
    /**
     * Extract booking details from call with automatic source selection
     *
     * Main orchestration method that:
     * 1. Checks for Retell custom_analysis_data
     * 2. Falls back to transcript parsing if needed
     * 3. Validates extracted data
     * 4. Calculates confidence score
     *
     * @param Call $call Call record with transcript and analysis
     * @return array|null Booking details or null if extraction failed
     */
    public function extract(Call $call): ?array;

    /**
     * Extract booking details from Retell's custom_analysis_data
     *
     * Parses structured data from Retell AI's analysis including:
     * - appointment_date_time
     * - patient_full_name / caller_full_name
     * - reason_for_visit
     * - appointment_made flag
     * - first_visit, insurance_type, etc.
     *
     * @param array $customData Retell's custom_analysis_data array
     * @return array|null Booking details with high confidence (100) or null
     */
    public function extractFromRetellData(array $customData): ?array;

    /**
     * Extract booking details from call transcript
     *
     * Uses pattern matching and NLP techniques to extract:
     * - Date patterns (ordinal, numeric, relative)
     * - Time patterns (German words, numeric)
     * - Service mentions
     * - Weekdays and relative days
     *
     * Supports German language patterns extensively.
     *
     * @param Call $call Call record with transcript
     * @return array|null Booking details with calculated confidence or null
     */
    public function extractFromTranscript(Call $call): ?array;

    /**
     * Parse date/time from string with multiple format support
     *
     * Handles various formats:
     * - Ordinal German dates (e.g., "erster Zehnter" = October 1st)
     * - Month names (e.g., "27. September 2025")
     * - Relative dates (heute, morgen, übermorgen)
     * - Weekdays (Montag, Dienstag, etc.)
     * - Numeric dates (01.10.2025)
     *
     * @param string $dateString Date string to parse
     * @param array $context Additional context for parsing
     * @return Carbon|null Parsed date/time or null
     */
    public function parseDateTime(string $dateString, array $context = []): ?Carbon;

    /**
     * Parse German ordinal date format
     *
     * Examples:
     * - "erster Zehnter" → October 1st
     * - "zweiten November" → November 2nd
     * - "dritten März" → March 3rd
     *
     * @param string $ordinalDate Ordinal date string
     * @return Carbon|null Parsed date or null
     */
    public function parseGermanOrdinalDate(string $ordinalDate): ?Carbon;

    /**
     * Parse German time expressions
     *
     * Examples:
     * - "vierzehn uhr" → 14:00
     * - "vierzehn uhr dreißig" → 14:30
     * - "16 uhr" → 16:00
     * - "17:30 uhr" → 17:30
     *
     * @param string $transcript Full transcript for context
     * @param Carbon $date Base date to apply time to
     * @return Carbon Date with parsed time applied
     */
    public function parseGermanTime(string $transcript, Carbon $date): Carbon;

    /**
     * Extract service name from transcript
     *
     * Recognizes German service names:
     * - Haarschnitt → Haircut
     * - Färben → Coloring
     * - Tönung → Tinting
     * - Styling → Styling
     * - Beratung → Consultation
     *
     * @param string $transcript Call transcript
     * @return string|null Extracted service name or null
     */
    public function extractServiceName(string $transcript): ?string;

    /**
     * Calculate confidence score for extracted booking details
     *
     * Factors considered:
     * - Number of data points extracted
     * - Presence of specific date/time information
     * - Service name identified
     * - Pattern match quality
     *
     * @param array $bookingDetails Extracted booking details
     * @param string $source Source of extraction ('retell' or 'transcript')
     * @return int Confidence score (0-100)
     */
    public function calculateConfidence(array $bookingDetails, string $source): int;

    /**
     * Validate extracted booking details
     *
     * Checks:
     * - Date is in the future (or reasonable past threshold)
     * - Time is within business hours (8:00-20:00)
     * - Required fields present
     * - Data format consistency
     *
     * @param array $bookingDetails Booking details to validate
     * @return bool True if valid, false otherwise
     */
    public function validateBookingDetails(array $bookingDetails): bool;

    /**
     * Fix past dates by adjusting to future occurrence
     *
     * Retell sometimes sends dates with wrong year.
     * This method intelligently adjusts to the next logical occurrence.
     *
     * @param Carbon $date Date that might be in the past
     * @return Carbon Adjusted date in the future
     */
    public function fixPastDate(Carbon $date): Carbon;

    /**
     * Extract date patterns from transcript
     *
     * Returns matched patterns and their types:
     * - weekday: Monday-Sunday
     * - relative_day: heute, morgen, übermorgen
     * - ordinal_date: erster Zehnter
     * - full_date: 27. September 2025
     * - time: 14 uhr, vierzehn uhr
     *
     * @param string $transcript Call transcript
     * @return array Matched patterns with types
     */
    public function extractDatePatterns(string $transcript): array;

    /**
     * Determine booking details format/structure
     *
     * Returns standardized booking details array:
     * [
     *   'starts_at' => 'Y-m-d H:i:s',
     *   'ends_at' => 'Y-m-d H:i:s',
     *   'service' => 'Service Name',
     *   'service_name' => 'Service Name',
     *   'duration_minutes' => 45,
     *   'patient_name' => 'Customer Name',
     *   'extracted_data' => [...],
     *   'confidence' => 0-100
     * ]
     *
     * @param Carbon $startTime Start time
     * @param int $duration Duration in minutes
     * @param string|null $service Service name
     * @param array $extractedData Raw extracted data
     * @param int $confidence Confidence score
     * @return array Standardized booking details
     */
    public function formatBookingDetails(
        Carbon $startTime,
        int $duration,
        ?string $service,
        array $extractedData,
        int $confidence
    ): array;
}
