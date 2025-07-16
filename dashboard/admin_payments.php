<?php
require_once '../includes/auth.php';
require_role('admin');
require_once '../includes/db.php';

$errors = [];
$success = '';

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $payment_id = $_POST['payment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$payment_id || !$status) {
        $errors[] = 'Payment ID and status are required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE student_fees SET payment_status = ? WHERE id = ?');
            $stmt->execute([$status, $payment_id]);
            $success = 'Payment status updated successfully.';
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch payments with student and fee info
$stmt = $pdo->query('SELECT sf.*, u.name as student_name, f.amount, ft.name as fee_type_name FROM student_fees sf JOIN student_profiles sp ON sf.student_id = sp.id JOIN users u ON sp.user_id = u.id JOIN fees f ON sf.fee_id = f.id JOIN fee_types ft ON f.fee_type_id = ft.id ORDER BY sf.created_at DESC');
$payments = $stmt->fetchAll();

?>

<?php include '../includes/header.php'; ?>
<div class="flex flex-col lg:flex-row">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 bg-gradient-to-br from-gray-50 to-gray-100">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Payment Management</h1>

            <?php if ($errors): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                    <ul class="list-disc pl-5 space-y-1 text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">All Payments</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($payment['fee_type_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($payment['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($payment['payment_status']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($payment['payment_date'] ?? ''); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <select name="status" class="border border-gray-300 rounded px-2 py-1">
                                            <option value="pending" <?php if ($payment['payment_status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="paid" <?php if ($payment['payment_status'] === 'paid') echo 'selected'; ?>>Paid</option>
                                            <option value="partial" <?php if ($payment['payment_status'] === 'partial') echo 'selected'; ?>>Partial</option>
                                        </select>
                                        <button type="submit" name="update_payment" class="ml-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>
