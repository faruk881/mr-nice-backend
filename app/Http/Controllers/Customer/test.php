<?php 
// Validate request
        $validated = $request->validated();

        // Get the request
        $pickupAddress   = $validated['pickup_address'];
        $pickupLat       = $validated['pickup_lat'];
        $pickupLong      = $validated['pickup_lon'];
        $pickupNotes     = $validated['pickup_notes'] ?? null;
        $deliveryAddress = $validated['delivery_address'];
        $deliveryLat     = $validated['delivery_lat'];
        $deliveryLong    = $validated['delivery_lon'];
        $deliveryNotes   = $validated['delivery_notes'] ?? null;
        $packageItems    = $validated['items'];
        $packageSize     = $validated['package_size'];
        $additionalNotes = $validated['additional_notes'] ?? null;
        $bookingDate     = $validated['booking_date'];


        // Determine Departure Time
        // We default to 9:00 AM of the booking date or today to avoid midnight traffic anomalies
        $departureTime = isset($bookingDate)
            ? Carbon::parse($bookingDate)->setTime(9, 0)->toIso8601String()
            : Carbon::now()->setTime(9, 0)->toIso8601String();
        
        // Get the distance using lon and lat using google api
        $distanceService = $distanceService->distanceMeasure('google',$departureTime,$pickupLat,$pickupLong,$deliveryLat,$deliveryLong);

        // Get the distance
        $distance = $distanceService['distanceKm'];
        
        // Get the time            
        $time = $distanceService['durationMinutes'];
        
        // Get Delivery pricing settings
        $settings = DeliveryFeeSetting::first();

        $baseFare = $settings->base_fare;
        $pricePerKm = $settings->per_km_fee;
        $distanceFee = $distance*$pricePerKm;

        $packagePrices = [
            'small' => (string) $settings->small_package_fee,
            'medium' => (string) $settings->medium_package_fee,
            'large' => (string) $settings->large_package_fee,
        ];

        // Get package size
        $packageSize = $request->package_size;
        $packagePrice = $packagePrices[$packageSize];

        $totalFee = max($distanceFee+$packagePrice,$baseFare);

        // Create order
        $order = Order::create([
            'customer_id'      => auth()->id(),
            'order_number'     => strtoupper(Str::random(10)), // unique order number
            'pickup_address'   => $pickupAddress,
            'pickup_lat'       => $pickupLat,
            'pickup_long'      => $pickupLong,
            'pickup_notes'     => $pickupNotes,
            'delivery_address' => $deliveryAddress,
            'delivery_lat'     => $deliveryLat,
            'delivery_long'    => $deliveryLong,
            'delivery_notes'   => $deliveryNotes,
            'package_items'    => $packageItems,
            'package_size'     => $packageSize,
            'additional_notes' => $additionalNotes,
            'distance'         => $distance,
            'base_fare'        => $baseFare,
            'per_km_fee'       => $pricePerKm,
            'package_fee'      => $packagePrice,
            'total_fee'        => $totalFee,
            'status'           => 'pending_payment',
            'booking_date'     => $bookingDate
        ]);

        // Generate and save order id
        $order->order_number = 'LX-'.str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $order->save();

        // Return response
        $estimates = [
            'distance' => $order->distance,
            'per_km_fee' => $order->per_km_fee,
            'package_fee' => $packagePrice,
            'total_fee' => $order->total_fee,
            
        ];

        $data = [
            'estimates' => $estimates,
            'order' => $order,

        ];

        return apiSuccess('Delivery request created successfully!', $data, 201);