import 'package:flutter/material.dart';
import '../config/api_config.dart';
import '../services/sync_manager.dart';
import '../services/field_operations_adapter.dart';

class OrderEntryScreen extends StatefulWidget {
  final String token;
  final String customerId;
  final String outletName;

  const OrderEntryScreen({
    super.key,
    required this.token,
    required this.customerId,
    required this.outletName,
  });

  @override
  State<OrderEntryScreen> createState() => _OrderEntryScreenState();
}

class _OrderEntryScreenState extends State<OrderEntryScreen> {
  late final FieldOperationsAdapter _adapter;
  final double _creditLimit = 50000.0;
  final double _currentBalance = 12500.0;

  // SKU Catalog with Base Unit Prices
  final List<Map<String, dynamic>> _catalog = [
    {
      'id': 'SKU-SAFARI-1L',
      'name': 'Safari Fresh Juice 1L',
      'category': 'Juices',
      'basePrice': 150.0,
      'qty': 0,
    },
    {
      'id': 'SKU-WATER-500',
      'name': 'Safari Spring Water 500ml',
      'category': 'Water',
      'basePrice': 50.0,
      'qty': 0,
    },
    {
      'id': 'SKU-ENERGY-250',
      'name': 'Safari Energy Drink 250ml',
      'category': 'Energy',
      'basePrice': 100.0,
      'qty': 0,
    },
  ];

  @override
  void initState() {
    super.initState();
    final syncManager = SyncManager(
      baseUrl: ApiConfig.baseUrl,
      authToken: widget.token,
      tenantHeader: ApiConfig.defaultTenant,
    );
    _adapter = FieldOperationsAdapter(syncManager: syncManager);
  }

  // 7-Tier Price Waterfall Engine Computation
  Map<String, double> _computeWaterfall() {
    double grossTotal = 0.0;
    int totalQty = 0;

    for (var item in _catalog) {
      int q = item['qty'] as int;
      double price = item['basePrice'] as double;
      grossTotal += price * q;
      totalQty += q;
    }

    // Tier 1: Base Gross Total
    double baseGross = grossTotal;

    // Tier 2: Channel Discount (5% off)
    double channelDiscount = baseGross * 0.05;
    double afterChannel = baseGross - channelDiscount;

    // Tier 3: Volume Tier Discount (3% off if totalQty >= 10)
    double volumeDiscount = totalQty >= 10 ? afterChannel * 0.03 : 0.0;
    double afterVolume = afterChannel - volumeDiscount;

    // Tier 4: Promo Discount (2% off active promotion)
    double promoDiscount = afterVolume * 0.02;
    double afterPromo = afterVolume - promoDiscount;

    // Tier 5: Customer Tier Discount (4% Key Account Tier)
    double customerTierDiscount = afterPromo * 0.04;
    double netSubtotal = afterPromo - customerTierDiscount;

    // Tier 6: Subtotal
    // Tier 7: VAT Tax (16%)
    double vatTax = netSubtotal * 0.16;
    double finalGrandTotal = netSubtotal + vatTax;

    return {
      'baseGross': baseGross,
      'channelDiscount': channelDiscount,
      'volumeDiscount': volumeDiscount,
      'promoDiscount': promoDiscount,
      'customerTierDiscount': customerTierDiscount,
      'netSubtotal': netSubtotal,
      'vatTax': vatTax,
      'finalGrandTotal': finalGrandTotal,
    };
  }

  void _saveDraftOrder() {
    final waterfall = _computeWaterfall();
    final double grandTotal = waterfall['finalGrandTotal']!;

    if (grandTotal <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select at least 1 SKU quantity.')),
      );
      return;
    }

    final double remainingCredit = _creditLimit - _currentBalance;
    if (grandTotal > remainingCredit) {
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          backgroundColor: const Color(0xFF1E293B),
          title: const Text('Credit Limit Exceeded', style: TextStyle(color: Colors.redAccent)),
          content: Text(
            'Order Total KES ${grandTotal.toStringAsFixed(2)} exceeds available credit limit KES ${remainingCredit.toStringAsFixed(2)}. (Limit: KES ${_creditLimit.toStringAsFixed(0)})',
            style: const TextStyle(color: Colors.grey),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('OK', style: TextStyle(color: Colors.indigoAccent))),
          ],
        ),
      );
      return;
    }

    final String clientUuid = 'ORD-${DateTime.now().millisecondsSinceEpoch}';
    final itemsList = _catalog
        .where((sku) => (sku['qty'] as int) > 0)
        .map((sku) => {
              'sku_id': sku['id'],
              'sku_name': sku['name'],
              'qty': sku['qty'],
              'unit_price': sku['basePrice'],
            })
        .toList();

    _adapter.createDraftOrder(
      clientUuid: clientUuid,
      customerId: widget.customerId,
      outletName: widget.outletName,
      items: itemsList,
      totalAmount: grandTotal,
    );

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Draft Order Saved! Total: KES ${grandTotal.toStringAsFixed(2)}'),
        backgroundColor: const Color(0xFF10B981),
      ),
    );

    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final waterfall = _computeWaterfall();

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        title: Text('Order Entry: ${widget.outletName}'),
        backgroundColor: const Color(0xFF0F172A),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // Outlet & Credit Overview Header
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(widget.outletName, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                      Text('Credit Limit: KES ${_creditLimit.toStringAsFixed(0)}', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Text('Available Credit', style: TextStyle(color: Colors.grey, fontSize: 11)),
                      Text(
                        'KES ${(_creditLimit - _currentBalance).toStringAsFixed(2)}',
                        style: const TextStyle(color: Colors.greenAccent, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // SKU Catalog List
            Expanded(
              child: ListView.builder(
                itemCount: _catalog.length,
                itemBuilder: (context, index) {
                  final sku = _catalog[index];
                  final int qty = sku['qty'] as int;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.white10),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(sku['name'] as String, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                              Text('Base Price: KES ${(sku['basePrice'] as double).toStringAsFixed(2)}', style: const TextStyle(color: Colors.indigoAccent, fontSize: 12)),
                            ],
                          ),
                        ),
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.remove_circle_outline, color: Colors.redAccent),
                              onPressed: () {
                                if (qty > 0) {
                                  setState(() => sku['qty'] = qty - 1);
                                }
                              },
                            ),
                            Text('$qty', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                            IconButton(
                              icon: const Icon(Icons.add_circle_outline, color: Colors.greenAccent),
                              onPressed: () {
                                setState(() => sku['qty'] = qty + 1);
                              },
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),

            // 7-Tier Price Waterfall Engine Breakdown Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('7-Tier Price Waterfall Breakdown', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                  const Divider(color: Colors.white10),
                  _waterfallRow('1. Gross Base Amount', 'KES ${waterfall['baseGross']!.toStringAsFixed(2)}'),
                  _waterfallRow('2. Channel Discount (-5%)', '- KES ${waterfall['channelDiscount']!.toStringAsFixed(2)}', isDiscount: true),
                  _waterfallRow('3. Volume Tier Discount (-3%)', '- KES ${waterfall['volumeDiscount']!.toStringAsFixed(2)}', isDiscount: true),
                  _waterfallRow('4. Promo Discount (-2%)', '- KES ${waterfall['promoDiscount']!.toStringAsFixed(2)}', isDiscount: true),
                  _waterfallRow('5. Customer Tier (-4%)', '- KES ${waterfall['customerTierDiscount']!.toStringAsFixed(2)}', isDiscount: true),
                  _waterfallRow('6. Net Subtotal', 'KES ${waterfall['netSubtotal']!.toStringAsFixed(2)}'),
                  _waterfallRow('7. VAT Tax (+16%)', '+ KES ${waterfall['vatTax']!.toStringAsFixed(2)}'),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Final Grand Total', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(
                        'KES ${waterfall['finalGrandTotal']!.toStringAsFixed(2)}',
                        style: const TextStyle(color: Colors.greenAccent, fontWeight: FontWeight.bold, fontSize: 18),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _saveDraftOrder,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6366F1),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: const Text('Save Order & Reserve Credit', style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _waterfallRow(String label, String value, {bool isDiscount = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 11)),
          Text(value, style: TextStyle(color: isDiscount ? Colors.greenAccent : Colors.grey, fontSize: 11, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
