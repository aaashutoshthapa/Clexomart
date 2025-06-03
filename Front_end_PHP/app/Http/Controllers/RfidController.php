<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRfidRequest;
use App\Models\RfidRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;          // ← add
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    /**
     * POST /api/rfid
     */
    public function store(StoreRfidRequest $request): JsonResponse
    {
        $read = RfidRead::create([
            'rfid' => strtoupper($request->uid),
            'time' => Carbon::now(),     // ← current date-time for the "time" column
        ]);

        return response()->json([
            'id'   => $read->rfid_id,
            'rfid' => $read->rfid,
        ], 201);
    }
    
    /**
     * GET /api/rfid/recent
     * Retrieve recent RFID scans
     */
    public function getRecent(Request $request): JsonResponse
    {
        try {
            // Get the 'since' parameter, default to 5 minutes ago if not provided
            $since = $request->query('since') 
                ? Carbon::parse($request->query('since')) 
                : Carbon::now()->subMinutes(5);
            
            // Get recent scans
            $scans = RfidRead::where('time', '>', $since)
                ->orderBy('time', 'asc')
                ->get(['rfid_id', 'rfid', 'time']);
            
            return response()->json([
                'success' => true,
                'scans' => $scans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving RFID scans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/rfid/{uid}/product
     * Get product information for a specific RFID UID
     */
    public function getProductByRfid($uid): JsonResponse
    {
        try {
            // Get the product associated with this RFID UID
            $pdo = \DB::connection()->getPdo();
            
            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.unit_price,
                    p.stock
                FROM 
                    RFID_PRODUCT rp
                JOIN 
                    PRODUCT p ON p.product_id = rp.product_id
                WHERE 
                    rp.rfid = :rfid
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':rfid', $uid);
            $stmt->execute();
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'No product found for this RFID UID'
                ]);
            }
            
            // Handle case sensitivity for Oracle column names
            $productData = [
                'product_id' => $product['PRODUCT_ID'] ?? $product['product_id'] ?? null,
                'product_name' => $product['PRODUCT_NAME'] ?? $product['product_name'] ?? 'Unknown Product',
                'description' => $product['DESCRIPTION'] ?? $product['description'] ?? '',
                'unit_price' => $product['UNIT_PRICE'] ?? $product['unit_price'] ?? 0,
                'stock' => $product['STOCK'] ?? $product['stock'] ?? 0
            ];
            
            return response()->json([
                'success' => true,
                'product_id' => $productData['product_id'],
                'product_name' => $productData['product_name'],
                'description' => $productData['description'],
                'unit_price' => $productData['unit_price'],
                'stock' => $productData['stock']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product information: ' . $e->getMessage()
            ], 500);
        }
    }
}
