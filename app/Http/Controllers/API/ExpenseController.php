<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of expenses from the user's company.
     * GET /api/expenses
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Expense::query()->where('company_id', $user->company_id);
        
        // If employee, show only their expenses
        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        }
        
        // Apply search filters if provided
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        
        if ($request->has('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }
        
        // Apply date range filters if provided
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        // Apply min/max amount filters if provided
        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        
        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }
        
        // Load relationships and paginate
        $expenses = $query->with('user')
                         ->orderBy('created_at', 'desc')
                         ->paginate($request->per_page ?? 15);
        
        return $this->paginatedResponse($expenses, 'Expenses retrieved successfully');
    }

    /**
     * Store a newly created expense.
     * POST /api/expenses
     * 
     * @param ExpenseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ExpenseRequest $request)
    {
        $user = $request->user();
        
        $expense = Expense::create([
            'title' => $request->title,
            'amount' => $request->amount,
            'category' => $request->category,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ]);
        
        return $this->successResponse($expense, 'Expense created successfully', 201);
    }

    /**
     * Display the specified expense.
     * GET /api/expenses/{id}
     * 
     * @param Request $request
     * @param Expense $expense
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Expense $expense)
    {
        $user = $request->user();
        
        // Check if expense belongs to user's company
        if ($expense->company_id !== $user->company_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // If employee, check if expense belongs to them
        if ($user->isEmployee() && $expense->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // Load the user relationship
        $expense->load('user');
        
        return $this->successResponse($expense, 'Expense retrieved successfully');
    }

    /**
     * Update the specified expense (Managers & Admins only).
     * PUT /api/expenses/{id}
     * 
     * @param ExpenseRequest $request
     * @param Expense $expense
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ExpenseRequest $request, Expense $expense)
    {
        $user = $request->user();
        
        // Check if expense belongs to user's company
        if ($expense->company_id !== $user->company_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // Check if user is admin or manager
        if (!$user->isAdmin() && !$user->isManager()) {
            return $this->errorResponse('Only managers and admins can update expenses', 403);
        }
        
        // Update expense
        $expense->fill($request->validated());
        $expense->save();
        
        return $this->successResponse($expense, 'Expense updated successfully');
    }

    /**
     * Remove the specified expense (Admins only).
     * DELETE /api/expenses/{id}
     * 
     * @param Request $request
     * @param Expense $expense
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Expense $expense)
    {
        $user = $request->user();
        
        // Check if expense belongs to user's company
        if ($expense->company_id !== $user->company_id) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        
        // Check if user is admin
        if (!$user->isAdmin()) {
            return $this->errorResponse('Only admins can delete expenses', 403);
        }
        
        $expense->delete();
        
        return $this->successResponse(null, 'Expense deleted successfully');
    }
}