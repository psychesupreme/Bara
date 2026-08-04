import 'package:flutter/material.dart';

class Customer360Screen extends StatelessWidget {
  final String token;
  final String customerId;
  final String outletName;

  const Customer360Screen({
    super.key,
    required this.token,
    required this.customerId,
    required this.outletName,
  });

  @override
  Widget build(BuildContext context) {
    const double creditLimit = 500000.0;
    const double outstandingBalance = 125000.0;
    const double creditUtilization = (outstandingBalance / creditLimit) * 100.0;

    final List<Map<String, dynamic>> topSkus = [
      {'name': 'Safari Fresh Juice 1L', 'sku': 'SFJ-1000', 'casesThisMonth': 140, 'trend': '+12%'},
      {'name': 'Safari Spring Water 500ml', 'sku': 'SSW-500', 'casesThisMonth': 280, 'trend': '+18%'},
      {'name': 'Safari Energy Drink 250ml', 'sku': 'SED-250', 'casesThisMonth': 95, 'trend': '-4%'},
    ];

    final List<Map<String, dynamic>> recommendedItems = [
      {'name': 'Safari Sparkling Water 1L', 'reason': 'High Category Demand', 'suggestedCases': 20},
      {'name': 'Safari Tropical Nectar 500ml', 'reason': 'Active Trade Promo (Buy 5 Get 1 Free)', 'suggestedCases': 15},
    ];

    return Scaffold(
      backgroundColor: const Color(0xFF0B0F19),
      appBar: AppBar(
        title: Text('Customer 360: $outletName'),
        backgroundColor: const Color(0xFF0F172A),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Commercial Profile & Tax PIN Header
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
                  Text(
                    outletName,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'KRA Tax PIN: P0511223344A • Channel: Key Account Tier 1',
                    style: TextStyle(color: Colors.grey, fontSize: 12),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: const [
                      Text('Days Since Last Order', style: TextStyle(color: Colors.grey, fontSize: 12)),
                      Text('4 Days Ago (Delivered)', style: TextStyle(color: Colors.greenAccent, fontWeight: FontWeight.bold, fontSize: 13)),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Credit Utilization Card
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
                      const Text('Credit Utilization', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                      Text(
                        '${creditUtilization.toStringAsFixed(1)}%',
                        style: TextStyle(
                          color: creditUtilization > 80 ? const Color(0xFFF43F5E) : Colors.amberAccent,
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  LinearProgressIndicator(
                    value: creditUtilization / 100.0,
                    backgroundColor: Colors.white10,
                    color: creditUtilization > 80 ? const Color(0xFFF43F5E) : const Color(0xFFF59E0B),
                    minHeight: 8,
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: const [
                      Text('Approved Limit: KES 500,000', style: TextStyle(color: Colors.grey, fontSize: 11)),
                      Text('Available: KES 375,000', style: TextStyle(color: Colors.cyanAccent, fontWeight: FontWeight.bold, fontSize: 11)),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Top Buying SKUs (Purchase History)
            const Text('Top Buying SKUs (Last 30 Days)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 10),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: topSkus.length,
              itemBuilder: (context, index) {
                final sku = topSkus[index];
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
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
                          Text(sku['name'] as String, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                          Text('SKU: ${sku['sku']}', style: const TextStyle(color: Colors.grey, fontSize: 11)),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text('${sku['casesThisMonth']} Cases', style: const TextStyle(color: Colors.indigoAccent, fontWeight: FontWeight.bold, fontSize: 13)),
                          Text(sku['trend'] as String, style: TextStyle(color: sku['trend'].toString().startsWith('+') ? Colors.greenAccent : const Color(0xFFF43F5E), fontSize: 11)),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
            const SizedBox(height: 20),

            // Recommended Order Items
            const Text('Recommended Order Items (AI Engine)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 10),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: recommendedItems.length,
              itemBuilder: (context, index) {
                final rec = recommendedItems[index];
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6366F1).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFF6366F1).withValues(alpha: 0.3)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(rec['name'] as String, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                          Text(rec['reason'] as String, style: const TextStyle(color: Colors.amberAccent, fontSize: 11)),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '+${rec['suggestedCases']} Cases',
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
            const SizedBox(height: 24),

            // Proceed to Step 3 Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton.icon(
                onPressed: () {
                  Navigator.pop(context, true);
                },
                icon: const Icon(Icons.arrow_forward, color: Colors.white),
                label: const Text('Proceed to Step 3: Merchandising Audit', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6366F1),
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
