<?php

/**
 * A PHP class for validating EAN, IMEI, ISBN, GTIN, SSCC, GSIN, UPC and other barcodes.
 *
 * @package BarcodeValidator
 * @author    Ivan Melgrati
 * @version   v1.1.3
 */

	/**
	 * A PHP class for validating EAN, IMEI, ISBN, GTIN, SSCC, GSIN, UPC and other similar codes.
	 * 
	 * @author    Ivan Melgrati
	 * @copyright Copyright 2018 by Ivan Melgrati
	 * @link      
	 * @license   https://github.com/imelgrat/barcode-validator/blob/master/LICENSE
	 */
	class BarcodeValidator
	{
		/**
		 * Constructor. 
		 * @return BarcodeValidator
		 */
		public function __construct()
		{ }

		/**
		 * Calculate a check digit using the Modulo-10 algorithm used for EAN/UPC/GTIN codes.
		 *
		 * @link https://en.wikipedia.org/wiki/Check_digit
		 * @link http://www.gs1.org/how-calculate-check-digit-manually
		 * @param  string $code the code to calculate the check digit for
		 * @return integer 
		 */
		protected static function calculateEANCheckDigit($code)
		{
			// Split odd-/even-positioned digits (except the last one).
			preg_match_all('/(\d)(\d){0,1}/', $code, $digits);
			$oddDigits = array_sum($digits[1]);
			$evenDigits = array_sum($digits[2]);
			if (strlen($code) % 2 == 0) {
				$evenDigits *= 3;
			} else {
				$oddDigits *= 3;
			}
			$sum = $oddDigits + $evenDigits;
			// nearest equal or higher multiple of ten
			$multiple = ceil($sum / 10) * 10;
			$checkDigit = $multiple - $sum;
			return $checkDigit;
		}

		/**
		 * Determine whether a code is valid using the check-digit algorithm. It also validates whether the code has the right length and that it's composed of only digits.
		 * 
		 * A check digit is a form of redundancy check used for error detection on identification numbers, such as bank account numbers, which are used in an application where they will at least sometimes be input manually. 
		 * 
		 * It is analogous to a binary parity bit used to check for errors in computer-generated data. It consists of one or more digits computed by an algorithm from the other digits (or letters) in the sequence input.
		 * 
		 * @link https://en.wikipedia.org/wiki/Check_digit
		 * @param  string $code the code to validate
		 * @param  int $length the code's length (e.g. 13 for EAN13, 12 for UPC-A, etc.)
		 * @return bool 
		 */
		protected static function validateEANCheckDigit($code, $length)
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			// Check if the string length matches the code's desired length
			if (strlen($code) == $length) {
				// If the string contains only digits
				if (preg_match('/^[0-9]+$/', $code)) {
					if (BarcodeValidator::calculateEANCheckDigit(substr($code, 0, -1)) == substr($code, -1, 1)) {
						return true;
					} else {
						return false;
					}
				} else // The code has invalid characters
				{
					return false;
				}
			} else // The code has the wrong length, it's invalid
			{
				return false;
			}
		}
		/**
		 * Replace a number by the sum of all its digits.
		 * 
		 * This funtion is used by {@link BarcodeValidator::calculateLuhnCheckDigit()} to add all the single digits of each number
		 *
		 * @param integer $number the number to get the sum of digits from
		 * @param integer $index the array index
		 * @return array
		 */
		protected static function sumAllDigits(&$number, $index = 0)
		{
			$number = array_sum(str_split(abs(2 * $number)));
		}

		/**
		 * Calculate a check digit using the Luhn Modulo-10 algorithm used for IMEI, Credit Card validation and others.
		 * 
		 * The check digit (x) is obtained by computing the sum of the rest of the digits then subtracting the units digit from 10. In algorithm form:
		 * 
		 * 1. Reverse the order of the digits in the number.
		 * 2. Take the first, third, ... and every odd-positioned digit in the reversed digits and sum them to form the partial sum s1
		 * 3. Multiply each digit by two and sum the digits of the result if the answer is greater than nine (e.g. 6 * 2 = 12 -> 2 + 1 = 3) to form partial sums for the even digits 
		 * 4. Sum the second, fourth ... and every even-positioned digit in the reversed digits to form s2:
		 * 5. Calculate the sum of s1 + s2
		 * 6. Take the last digit from that sum. If the digit is 0, that's the check digit. If not, substrack the number from 10 to obtain the check digit.
		 * 
		 * For instance
		 * 
		 * | Digits             | 4 |    9    |  0  |  1  |  5  |  4  |  2  |  0  |  3  |  2  |  3  |    7    |  5  |  1  |     |
		 * |:--------------------|:-:|:-------:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:-------:|:---:|:---:|:---:|
		 * | Double every other | 4 |    18   |  0  |  2  |  5  |  8  |  2  |  0  |  3  |  4  |  3  |    14   |  5  |  2  |     |
		 * | Sum all digits     | 4 | + (1+8) | + 0 | + 2 | + 5 | + 8 | + 2 | + 0 | + 3 | + 4 | + 3 | + (1+4) | + 5 | + 2 |  = 52  |
		 * 
		 * Check digit is: 10 - 2 = 8.
		 * 
		 * @link https://en.wikipedia.org/wiki/Luhn_algorithm
		 * @param  string $code the code to calculate the Luhn check digit for
		 * @return integer 
		 */
		protected static function calculateLuhnCheckDigit($code)
		{
			// Split odd-/even-positioned digits (except the last one).
			preg_match_all('/(\d)(\d){0,1}/', strrev($code), $digits);
			$odd_digits = array_sum($digits[2]);

			array_walk($digits[1], 'BarcodeValidator::sumAllDigits');
			$even_digits = array_sum($digits[1]);

			// Calculate check digit
			$checkdigit = (10 - (($odd_digits + $even_digits) % 10)) % 10;

			return $checkdigit;
		}

		/**
		 * Determine whether a code is valid using the Luhn algorithm. It also validates whether the code has the right length and that it's composed of only digits.
		 * 
		 * The Luhn algorithm or Luhn formula, also known as the "modulus 10" or "mod 10" algorithm, is a simple checksum formula used to validate a variety of identification numbers, such as credit card numbers, IMEI numbers and National Provider Identifier numbers in US and Canadian Social Insurance Numbers.
		 * 
		 * @link https://en.wikipedia.org/wiki/Check_digit
		 * @param  string $code the code to validate
		 * @param  int $length the code's length (e.g. 15 for IMEI numbers, 16 for credit cards, etc.)
		 * @return bool 
		 */
		protected static function validateLuhnCheckDigit($code, $length)
		{
			// Check if the string length matches the code's desired length
			if (strlen($code) == $length) {
				// If the string contains only digits
				if (preg_match('/^[0-9]+$/', $code)) {
					if (BarcodeValidator::calculateLuhnCheckDigit(substr($code, 0, -1)) == substr($code, -1, 1)) {
						return true;
					} else {
						return false;
					}
				} else // The code has invalid characters
				{
					return false;
				}
			} else // The code has the wrong length, it's invalid
			{
				return false;
			}
		}

		/**
		 * Calculate a check digit using the ISBN algorithm. The ISBN is error-detecting, but not error-correcting (unless it is known that only a single digit is erroneous). The ISBN detects any single-digit error, as well as most two-digit error resulting from transposing two digits. 
		 * 
		 * The ISBN (International Standard Book Number) is a unique numeric commercial book identifier. An ISBN is assigned to each edition and variation (except reprintings) of a book. For example, an e-book, a paperback and a hardcover edition of the same book would each have a different ISBN. ISBNs are 13 digits long if assigned on or after 1 January 2007, and 10 digits long if assigned before 2007. The method of assigning an ISBN is nation-based and varies from country to country, often depending on how large the publishing industry is within a country.
		 *
		 * The initial ISBN configuration of recognition was generated in 1967 based upon the 9-digit Standard Book Numbering (SBN) created in 1966. The 10-digit ISBN format was developed by the International Organization for Standardization (ISO) and was published in 1970 as international standard ISO 2108 (the SBN code can be converted to a ten digit ISBN by prefixing it with a zero). The last digit is a check digit which may be in the range 0-9 or X (where X is the Roman numeral for 10) for an ISBN-10, or 0-9 for an ISBN-13.
		 * 
		 * For ISBN-10 each of the first nine digits of the ten-digit ISBN (excluding the check digit itself) is multiplied by a number in a sequence from 10 to 2 and the remainder of the sum with (respect to 11) is computed. The resulting remainder, plus the check digit, must equal a multiple of 11 (either 0 or 11).
		 * 
		 * For ISBN-13 the check-digit is calculated using the {@link BarcodeValidator::calculateEANCheckDigit()} function, making it compatible with EAN codes. 
		 * 
		 * Therefore, the check digit is (11 minus the remainder of the sum of the products modulo 11) modulo 11. Taking the remainder modulo 11 a second time accounts for the possibility that the first remainder is 0 (without the second modulo operation the calculation could end up with 11-0 = 11 which is invalid).
		 * 
		 * | ISBN   |  1 |  8 |  4 | 1 |  4 |  6 | 2 | 0 | 1 | Total |
		 * |:--------|:--:|:--:|:--:|:-:|:--:|:--:|:-:|:-:|:-:|-------|
		 * | Weight | 10 |  9 |  8 | 7 |  6 |  5 | 4 | 3 | 2 |       |
		 * | Result | 10 | 72 | 32 | 7 | 24 | 30 | 8 | 0 | 2 | 185   |
		 * 
		 * Calculate (11 - (185 mod 11)) mod 11 = 2 which is the last digit from the original ISBN. Hence, the ISBN is valid.
		 * 
		 * @link https://en.wikipedia.org/wiki/International_Standard_Book_Number
		 * @param string $code The code to calculate the check digit for
		 * @return mixed (integer or X) 
		 */
		protected static function calculateISBNCheckDigit($code)
		{
			if (strlen($code) == 13) {
				$checkdigit = BarcodeValidator::calculateEANCheckDigit($code);
			} else {
				$checkdigit = 11 - ((10 * $code[0] + 9 * $code[1] + 8 * $code[2] + 7 * $code[3] + 6 * $code[4] + 5 * $code[5] + 4 * $code[6] + 3 * $code[7] + 2 * $code[8]) %
					11);
				if ($checkdigit == 10) {
					$checkdigit = 'X';
				}
			}

			return $checkdigit;
		}

		/**
		 * Determine whether a code is valid using the ISBN algorithm (either ISBN 10). It also validates whether the code has the right length.
		 * 
		 * For ISBN-10 each of the first nine digits of the ten-digit ISBN (excluding the check digit itself) is multiplied by a number in a sequence from 10 to 2 and the remainder of the sum with (respect to 11) is computed. The resulting remainder, plus the check digit, must equal a multiple of 11 (either 0 or 11). 
		 * 
		 * @link https://en.wikipedia.org/wiki/International_Standard_Book_Number
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		protected static function validateISBNCheckDigit($code)
		{
			// Check if the string length matches the code's desired length and contains only digits (after removing hyphens)
			if (strlen($code) == 10 && preg_match('/^[0-9]+$/', $code)) {
				if (BarcodeValidator::calculateISBNCheckDigit(substr($code, 0, -1)) == substr($code, -1, 1)) {
					return true;
				} else {
					return false;
				}
			} else // The code has the wrong length, it's invalid
			{
				return false;
			}
		}

		/**
		 * Determine whether an ISBN code is valid (either ISBN 10 or 13 digits). It also validates whether the code has the right length.
		 * 
		 * For ISBN-10 each of the first nine digits of the ten-digit ISBN (excluding the check digit itself) is multiplied by a number in a sequence from 10 to 2 and the remainder of the sum with (respect to 11) is computed. 
		 * The resulting remainder, plus the check digit, must equal a multiple of 11 (either 0 or 11). 
		 * For ISBN-13 the check-digit is calculated using the {@link BarcodeValidator::validateEANCheckDigit()} function, making it compatible with EAN codes.
		 * 
		 * @link https://en.wikipedia.org/wiki/International_Standard_Book_Number
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidISBN($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			if (strlen($code) == 13) {
				return BarcodeValidator::validateEANCheckDigit($code, 13);
			} else {
				return BarcodeValidator::validateISBNCheckDigit($code, 10);
			}
		}

		/**
		 * Determine whether a EAN-8 (GTIN-8) code is valid
		 * 
		 * EAN 8 is the short form of EAN-13. This code is only used if the article is too small for an EAN-13 code. 
		 * 
		 * EAN-8 codes are common throughout the world, and companies may also use them to encode RCN-8s (8-digit Restricted Circulation Numbers) used to identify own-brand products sold only in their stores. These are formatted as 02xx xxxx, 04xx xxxx or 2xxx xxxx.
		 * 
		 * An EAN-8 always has 8 digits:
		 * 
		 * 1. **Manufacturer Code**: 2- or 3-digit [GS1](http://www.gs1.org/) prefix (which is assigned to each national GS1 authority)
		 * 2. **Product Code**: 5- or 4-digit item reference element depending on the length of the GS1 prefix)
		 * 3. **Check digit**: The check digit is an additional digit, used to verify that a barcode has been entered correctly. 
		 * 
		 * @link https://en.wikipedia.org/wiki/EAN-8
		 * @link https://en.wikipedia.org/wiki/International_Article_Number
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidEAN8($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 8);
		}

		/**
		 * Determine whether a EAN-13 (GTIN-13) code is valid
		 * 
		 * The EAN barcode is primarily used in supermarkets to identify product at the point of sales. The products contain the EAN number or GTIN (Global Trade Item Number) to identify itself.
		 * 
		 * An EAN-13 always has 13 digits:
		 * 
		 * 1. **GS1 Prefix**: The first 3 digits - usually identifying the national [GS1](http://www.gs1.org/) Member Organization to which the manufacturer is registered (not necessarily where the product is actually made). The GS1 Prefix is equal to 978 when the EAN-13 symbol encodes a conversion of an International Standard Book Number (ISBN). 
		 * Likewise the prefix is equal to 979 for International Standard Music Number (ISMN) and 977 for International Standard Serial Number (ISSN).
		 * 2. **Manufacturer Code**: The manufacturer code is a unique code assigned to each manufacturer by the numbering authority indicated by the GS1 Prefix. All products produced by a given company will use the same manufacturer code.
		 * 3. **Product Code**: The product code is assigned by the manufacturer. The product code immediately follows manufacturer code. The total length of manufacturer code plus product code should be 9 or 10 digits depending on the length of country code(2-3 digits).
		 * 4. **Check digit**: The check digit is an additional digit, used to verify that a barcode has been entered correctly. 
		 * 
		 * @link https://en.wikipedia.org/wiki/International_Article_Number
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidEAN13($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 13);
		}

		/**
		 * Determine whether a EAN-14 code is valid
		 * 
		 * The EAN 14 code is used for traded goods.
		 * 
		 * An EAN-14 always has 14 digits:
		 * 
		 * 1. **Application Identifier**: The first two numbers are the Application Identifier of the EAN-128: (01). You cannot change them. The first digit is the "Logistic Variant", also named as "Packaging indicator".
		 * 2. **Product number**: The next 12 digits are representing the product number. Generally this is the EAN-13 number without the check digit.
		 * 3. **Check digit**: The check digit is an additional digit, used to verify that the code has been entered correctly. 
		 * 
		 * @link https://en.wikipedia.org/wiki/International_Article_Number
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidEAN14($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 14);
		}

		/**
		 * Determine whether a UPC-A code is valid
		 * 
		 * The UPC-A code is the standard version of the [UPC](https://en.wikipedia.org/wiki/Universal_Product_Code) code and has 12 digits.  The UPC code is a numeric code which is able to display digits from 0-9. It is also called UPC-12 and is very similar to the EAN code. 
		 * 
		 * The structure of the UPC-A code is as follows:
		 * 
		 * 1. **Type of content**: The first digit of the UPC-A code says what the code contains:
		 *    - 0: normal UPC Code
		 *    - 1: reserved
		 *    - 2: articles where the price varies by the weight: for example meat. The code is produced in the store and attached to the article.
		 *    - 3: National Drug Code (NDC) and National Health Related Items Code (HRI).
		 *    - 4: UPC Code which can be used without format limits
		 *    - 5: coupon
		 *    - 6: normal UPC Code
		 *    - 7: normal UPC Code
		 *    - 8: reserved
		 *    - 9: reserved
		 * 2. **UPC ID number**: The next 5 digits show the producer of the article (UPC ID number). This number is issued by the Uniform Code Council (UUC)
		 * 3. **Article number**: The seventh to eleventh digits show the individual article number issued by the producer.
		 * 4. **Check digit**: The check digit is an additional digit, used to verify that the code has been entered correctly. 
		 * 
		 * @link https://en.wikipedia.org/wiki/Universal_Product_Code
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidUPCA($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 12);
		}

		/**
		 * Determine whether a UPC-E code is valid
		 * 
		 * The UPC-E code is a short, 8-digit version of the UPC-A code, always starting with a zero. It is often used for small retail items. 
		 * The UPC code is a numeric code which is able to display digits from 0-9. 
		 * 
		 * The structure of the full-length UPC-E code is as follows:
		 * 1. **Number system digit**: Always zero. Might be missing in fully-compressed code version (6-digits).
		 * 2. **Compressed-form manufacturer/product number** It is created by suppressing trailing zeros in the manufacturers code and leading zeros in the product identification part of the bar code message.
		 *    - Manufacturer code must have 2 leading digits with 3 trailing zeros and the item number is limited to 3 digits (000 to 999). Sixth digit is 0.
		 *    - Manufacturer code must have 3 leading digits ending with "1" and 2 trailing zeros. The item number is limited to 3 digits. Sixth digit is 1
		 *    - Manufacturer code must have 3 leading digits ending with "2" and 2 trailing zeros. The item number is limited to 3 digits. Sixth digit is 2
		 *    - Manufacturer code must have 3 leading digits and 2 trailing zeros. The item number is limited to 2 digits (00 to 99). Sixth digit is 3
		 *    - Manufacturer code must have 4 leading digits with 1 trailing zero and the item number is limited to 1 digit (0 to9). Sixth digit is 4
		 *    - Manufacturer code has all 5 digits. The item number is limited to a single digit consisting of either 5,6,7,8 or 9. Sixth digit is either 5,6,7,8 or 9
		 * 3. **Check digit**: The check digit is an additional digit, used to verify that the code has been entered correctly. Might be missing in fully-compressed code version (6-digits).
		 * 
		 * @link http://www.gtin.info/upc/
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidUPCE($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);

			// If the string contains only digits
			if (!preg_match('/^[0-9]+$/', $code)) {
				return false;
			}

			$len = strlen($code);
			if ($len == 6) // No check digit, it's always correct
			{
				return true;
			}
			// Extract 6 digits from code (discard check and number system digits if necessary)
			switch (strlen($code)) {
				case 7: // 7th digit is check digit. No number system digit
					$upc_e_code = substr($code, 0, 6);
					$check_code = $code[6];
					break;
				case 8: // Both check and number system digits
					if ($code[0] != 0) {
						return false;
					}
					$upc_e_code = substr($code, 1, 6);
					$check_code = $code[7];
					break;
				default: // UPC-E must have between 6-8 digits
					return false;
					break;
			}
			// Convert UPC-E to UPC-A (to calculate check digit)
			switch ($upc_e_code[5]) {
				case "0":
					$ManufacturerNumber = ($upc_e_code[0] . $upc_e_code[1] . $upc_e_code[5] . "00");
					$ItemNumber = ("00" . $upc_e_code[2] . $upc_e_code[3] . $upc_e_code[4]);
					break;

				case "1":
					$ManufacturerNumber = ($upc_e_code[0] . $upc_e_code[1] . $upc_e_code[5] . "00");
					$ItemNumber = "00" . $upc_e_code[2] . $upc_e_code[3] . $upc_e_code[4];
					break;

				case "2":
					$ManufacturerNumber = $upc_e_code[0] . $upc_e_code[1] . $upc_e_code[5] . "00";
					$ItemNumber = "00" . $upc_e_code[2] . $upc_e_code[3] . $upc_e_code[4];
					break;

				case "3":
					$ManufacturerNumber = $upc_e_code[0] . $upc_e_code[1] . $upc_e_code[2] . "00";
					$ItemNumber = "000" . $upc_e_code[3] . $upc_e_code[4];
					break;

				case "4":
					$ManufacturerNumber = $upc_e_code[0] . $upc_e_code[1] . $upc_e_code[2] . $upc_e_code[3] . "0";
					$ItemNumber = "0000" . $upc_e_code[4];
					break;

				default:
					$ManufacturerNumber = ($upc_e_code[0] . $upc_e_code[1] . $upc_e_code[2] . $upc_e_code[3] . $upc_e_code[4]);
					$ItemNumber = ("0000" . $upc_e_code[5]);
					break;
			} //End Select

			return BarcodeValidator::validateEANCheckDigit("0" . $ManufacturerNumber . $ItemNumber . $check_code, 12);
		}

		/**
		 * Determine whether a GSIN code is valid.
		 * 
		 * The Global Shipment Identification Number (GSIN) is a globally unique number which is used to identify a grouping of logistic units which are part of the same shipment. 
		 * 
		 * The logistic units keep the same GSIN during all transport stages, from origin to final destination. The GSIN identifies the logical grouping of one or several logistic units, each identified with a separate SSCC (Serial Shipping Container Code).
		 * 
		 * GSIN also meets the requirements for UCR (Unique Consignment Reference) according to the World Customs Organisation, WCO.
		 * 
		 * 1. **GS1 Company Prefix**: (6-9 digits) This prefix identifies the national [GS1](http://www.gs1.org/) Member Organization to which the manufacturer is registered (not necessarily where the shipment occurs).
		 * 2. **Sequence number**: It is formed by 7-10 digits (depending on the length of the company prefix). It is recommended that to number shipments sequentially but it can be created in any sequence.
		 * 3. **Check digit**: The check digit is an additional digit, used to verify that the code has been entered correctly. 
		 * 
		 * @link http://www.gs1.se/en/our-standards/Identify/GSIN/
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidGSIN($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 17);
		}

		/**
		 * Determine whether a SSCC code is valid.
		 * 
		 * An SSCC (Serial Shipping Container Code) is used to provide a number logistic units. An SSCC enables manufacturers, suppliers, carriers and buyers to track a logistic unit from production to end customer. The can be used for tracing goods, warehouse management and efficient handling in transport.
		 * 
		 * 1. **A leading digit**: (1 digit between 1-9) The leading digit is a number between 1 and 9. Previously only the digit 3 was used which means there can be logistic and transport-related systems that only permit the use of SSCCs which start with 3. Note that if an SSCC is created based on the company registration number, the leading digit must always be 3.
		 * 2. **GS1 Company Prefix**: (6-9 digits) This prefix identifies the national [GS1](http://www.gs1.org/) Member Organization to which the manufacturer is registered (not necessarily where the shipment occurs).
		 * 3. **Sequence number**: It is formed by 7-10 digits (depending on the length of the company prefix). It is recommended that to number shipments sequentially but it can be created in any sequence.
		 * 4. **Check digit**: The check digit is an additional digit, used to verify that the code has been entered correctly. 
		 * 
		 * The same SSCC may not be used on two different logistic units, neither during transport or when they are handled in the warehouse of the transport buyer or goods recipient. This means that the life span of an SSCC, that is the period until the unit is unpacked or repacked, can be from a few to many years.
		 *
		 * The transport industry recommends that an SSCC should be available for re-use after 18 months, but only if it is certain that the SSCC is no longer in use. To estimate how many SSCCs your company needs you can, for example, use the number of logistic units handled in the most recent one or two year period.
		 *
		 * @link http://www.gs1.se/en/our-standards/Identify/sscc/
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidSSCC($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 18);
		}

		/**
		 * Determine whether a GLN code is valid
		 * 
		 * A GLN (Global Location Number) is used to uniquely identify a company or organisation. 
		 * 
		 * A GLN can also be used to number delivery places, invoicing addresses, workplaces, branches as well as functions or roles, such as goods recipient or authorised purchaser.
		 * 
		 * @link https://en.wikipedia.org/wiki/Global_Location_Number
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidGLN($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			return BarcodeValidator::validateEANCheckDigit($code, 13);
		}

		/**
		 * Determine whether an IMEI number is valid or IMEISV
		 * 
		 * The International Mobile Equipment Identity (IMEI) is a number, to identify 3GPP (i.e., GSM, UMTS and LTE) and iDEN mobile phones, as well as some satellite phones. 
		 * 
		 * It is usually found printed inside the battery compartment of the phone, but can also be displayed on-screen on most phones by entering *#06# on the dialpad, or alongside other system information in the settings menu on smartphone operating systems.
		 * 
		 * The IMEI (15 decimal digits: 14 digits plus a check digit) or IMEISV (16 digits) includes information on the origin, model, and serial number of the device. 
		 * 
		 * The structure of the IMEI/SV is specified in 3GPP TS 23.003. The model and origin comprise the initial 8-digit portion of the IMEI/SV, known as the Type Allocation Code (TAC). The remainder of the IMEI is manufacturer-defined, with a Luhn check digit at the end. The IMEISV drops the Luhn check digit in favor of an additional two digits for the Software Version Number (SVN)
		 * 
		 * @link https://en.wikipedia.org/wiki/International_Mobile_Station_Equipment_Identity
		 * @param  string $code the code to validate
		 * @return bool 
		 */
		public static function IsValidIMEI($code = '')
		{
			// Remove hyphens
			$code = str_replace('-', '', $code);
			if (preg_match('/^[0-9]+$/', $code)) {
				switch (strlen($code)) {
					case 14: // No check digit. always true
						return true;
						break;
					case 15: // IMEI number with check digit. Test check digit
						return BarcodeValidator::validateLuhnCheckDigit($code, 15);
						break;
					case 16: // It's a IMEISV code (IMEI Software Version). No check digit
						return true;
						break;
					default:
						return false;
				}
			}
		}
	}
