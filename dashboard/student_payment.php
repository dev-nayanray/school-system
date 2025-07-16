<?php
require_once '../includes/auth.php';
require_role('student');
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Get student profile id and class_id
$stmt = $pdo->prepare('SELECT sp.id as student_profile_id, sp.class_id FROM student_profiles sp WHERE sp.user_id = ?');
$stmt->execute([$user_id]);
$student_profile = $stmt->fetch();
$student_profile_id = $student_profile['student_profile_id'] ?? null;
$class_id = $student_profile['class_id'] ?? null;

if (!$student_profile_id) {
    die('Student profile not found.');
}

// Fetch student fees with payment status
$stmt = $pdo->prepare('
    SELECT sf.id as student_fee_id, ft.name as fee_type_name, f.amount, sf.amount_paid, sf.payment_status, f.due_date
    FROM student_fees sf
    JOIN fees f ON sf.fee_id = f.id
    JOIN fee_types ft ON f.fee_type_id = ft.id
    WHERE sf.student_id = ?
');
$stmt->execute([$student_profile_id]);
$student_fees = $stmt->fetchAll();

// Fetch fees for student's class (not assigned individually)
$class_fees = [];
if ($class_id) {
    $stmt = $pdo->prepare('
        SELECT NULL as student_fee_id, ft.name as fee_type_name, f.id as fee_id, f.amount, 0 as amount_paid, "pending" as payment_status, f.due_date
        FROM fees f
        JOIN fee_types ft ON f.fee_type_id = ft.id
        WHERE (f.class_id = ? OR f.class_id IS NULL)
        AND f.id NOT IN (
            SELECT fee_id FROM student_fees WHERE student_id = ?
        )
    ');
    $stmt->execute([$class_id, $student_profile_id]);
    $class_fees = $stmt->fetchAll();
}

?>

<?php include '../includes/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">My Fees & Payments</h1>

            <?php if (!empty($student_fees) || !empty($class_fees)): ?>
                <table class="min-w-full divide-y divide-gray-200 table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fee Type</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount Paid</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Payment Status</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach (array_merge($student_fees, $class_fees) as $fee): ?>
                            <tr class="hover:bg-gray-100">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fee['fee_type_name']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo number_format($fee['amount'], 2); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo number_format($fee['amount_paid'], 2); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo !empty($fee['due_date']) ? htmlspecialchars($fee['due_date']) : '<span class="text-gray-400 italic">No due date</span>'; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <?php
                                    $status = $fee['payment_status'];
                                    $status_classes = [
                                        'pending' => 'text-red-600 font-semibold',
                                        'partial' => 'text-yellow-600 font-semibold',
                                        'paid' => 'text-green-600 font-semibold',
                                    ];
                                    ?>
                                    <span class="<?php echo $status_classes[$status] ?? ''; ?>"><?php echo ucfirst($status); ?></span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <?php if ($fee['student_fee_id'] && $status !== 'paid'): ?>
                                        <form method="post" action="/school-system/payment_sslcommerz.php" target="_blank">
                                            <input type="hidden" name="student_fee_id" value="<?php echo $fee['student_fee_id']; ?>">
                                            <input type="hidden" name="amount" value="<?php echo $fee['amount'] - $fee['amount_paid']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Pay Now</button>
                                        </form>
                                    <?php elseif (!$fee['student_fee_id'] && $status !== 'paid'): ?>
                                        <form method="post" action="/school-system/payment_sslcommerz.php" target="_blank">
                                            <input type="hidden" name="fee_id" value="<?php echo $fee['fee_id']; ?>">
                                            <input type="hidden" name="amount" value="<?php echo $fee['amount']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Pay Now</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-green-600 font-semibold">Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No fees found.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
