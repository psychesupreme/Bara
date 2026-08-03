import 'package:flutter/material.dart';
import '../models/isar_models.dart';

class MerchandisingScreen extends StatefulWidget {
  final String token;
  final String customerId;
  final String outletName;

  const MerchandisingScreen({
    super.key,
    required this.token,
    required this.customerId,
    required this.outletName,
  });

  @override
  State<MerchandisingScreen> createState() => _MerchandisingScreenState();
}

class _MerchandisingScreenState extends State<MerchandisingScreen> {
  final List<Map<String, dynamic>> _mslSkus = [
    {'name': 'Safari Fresh Juice 1L', 'sku': 'SFJ-1000', 'inStock': true},
    {'name': 'Safari Spring Water 500ml', 'sku': 'SSW-500', 'inStock': true},
    {'name': 'Safari Energy Drink 250ml', 'sku': 'SED-250', 'inStock': false},
    {'name': 'Safari Sparkling Water 1L', 'sku': 'SPW-1000', 'inStock': true},
  ];

  double _shareOfShelfPercent = 48.0;
  final TextEditingController _competitorBrandController = TextEditingController(text: 'Brand X Juice');
  final TextEditingController _competitorPriceController = TextEditingController(text: '135');
  final TextEditingController _competitorNotesController = TextEditingController(text: 'Buy 1 Get 1 Promo');

  int get _inStockCount => _mslSkus.where((s) => s['inStock'] == true).length;
  double get _mslScore => (_inStockCount / _mslSkus.length) * 100.0;

  void _saveObservation() {
    final observation = IsarMerchObservation(
      id: DateTime.now().millisecondsSinceEpoch % 10000,
      clientUuid: 'MERCH-${DateTime.now().millisecondsSinceEpoch}',
      sequence: 1,
      customerId: widget.customerId,
      mslComplianceScore: _mslScore,
      shareOfShelfPercentage: _shareOfShelfPercent,
      isSynced: false,
    );

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Merchandising Audit Saved! MSL Score: ${_mslScore.toStringAsFixed(0)}%'),
        backgroundColor: const Color(0xFF10B981),
      ),
    );

    Navigator.pop(context, observation);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        title: Text('Merchandising: ${widget.outletName}'),
        backgroundColor: const Color(0xFF0F172A),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // MSL Availability Score Card
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
                      const Text('MSL Availability Score', style: TextStyle(color: Colors.grey, fontSize: 12)),
                      const SizedBox(height: 4),
                      Text(
                        '${_mslScore.toStringAsFixed(0)}%',
                        style: TextStyle(
                          color: _mslScore >= 70 ? Colors.greenAccent : Colors.amberAccent,
                          fontWeight: FontWeight.bold,
                          fontSize: 28,
                        ),
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _mslScore >= 70 ? Colors.green.withValues(alpha: 0.2) : Colors.amber.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      _mslScore >= 70 ? 'Compliant' : 'Needs Restock',
                      style: TextStyle(
                        color: _mslScore >= 70 ? Colors.greenAccent : Colors.amberAccent,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Must-Stock-List (MSL) SKU Checklist
            const Text('Must-Stock-List (MSL) Audit', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 10),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _mslSkus.length,
              itemBuilder: (context, index) {
                final item = _mslSkus[index];
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1E293B),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Colors.white10),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(item['name'] as String, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                          Text('SKU: ${item['sku']}', style: const TextStyle(color: Colors.grey, fontSize: 11)),
                        ],
                      ),
                      Switch(
                        value: item['inStock'] as bool,
                        activeThumbColor: Colors.greenAccent,
                        onChanged: (val) {
                          setState(() {
                            item['inStock'] = val;
                          });
                        },
                      ),
                    ],
                  ),
                );
              },
            ),
            const SizedBox(height: 24),

            // Share of Shelf (SOS) Metric
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Share of Shelf (SOS)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                      Text('${_shareOfShelfPercent.round()}%', style: const TextStyle(color: Colors.indigoAccent, fontWeight: FontWeight.bold, fontSize: 18)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Slider(
                    value: _shareOfShelfPercent,
                    min: 0,
                    max: 100,
                    divisions: 100,
                    activeColor: const Color(0xFF6366F1),
                    inactiveColor: Colors.white10,
                    onChanged: (val) {
                      setState(() => _shareOfShelfPercent = val);
                    },
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Competitor Intelligence Logging
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white10),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Competitor Intelligence Log', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _competitorBrandController,
                    style: const TextStyle(color: Colors.white),
                    decoration: const InputDecoration(
                      labelText: 'Competitor Brand Name',
                      labelStyle: TextStyle(color: Colors.grey),
                      filled: true,
                      fillColor: Color(0xFF0F172A),
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: _competitorPriceController,
                    keyboardType: TextInputType.number,
                    style: const TextStyle(color: Colors.white),
                    decoration: const InputDecoration(
                      labelText: 'Observed Price (KES)',
                      labelStyle: TextStyle(color: Colors.grey),
                      filled: true,
                      fillColor: Color(0xFF0F172A),
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: _competitorNotesController,
                    style: const TextStyle(color: Colors.white),
                    decoration: const InputDecoration(
                      labelText: 'Promotional Notes / POSM',
                      labelStyle: TextStyle(color: Colors.grey),
                      filled: true,
                      fillColor: Color(0xFF0F172A),
                      border: OutlineInputBorder(),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Save Audit Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton.icon(
                onPressed: _saveObservation,
                icon: const Icon(Icons.check_circle, color: Colors.white),
                label: const Text('Save Merchandising Audit', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFEC4899),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
