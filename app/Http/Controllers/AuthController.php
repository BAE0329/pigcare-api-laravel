<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\FarmSetup;
use App\Models\Pigs;
use App\Models\Feeds;
use App\Models\Events;
use App\Models\Event;
use App\Models\Revenue;
use App\Models\Transaction;
use App\Models\PigsReport;
use App\Models\FeedsInfo;
use App\Models\PigsInfo;
use App\Models\FeedUsage;
use Carbon\Carbon;
use Mail;
use App\Mail\MyDemoMail;
use App\Models\ForgotPassCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Storing new user details
     */
    public function register(Request $request)
    {
        try {
            $validateData = $request->validate([
                'farmname' => 'required',
                'birthdate' => 'required|date_format:Y-m-d',
                'password' => 'required|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$ %^&*-]).*$/',
            ]);
    
            DB::beginTransaction();
    
            // Check if farm_name already exists
            $existingUser = User::where('farm_name', $request->farmname)->first();
            if ($existingUser) {
                throw new \Exception('Farm name already exists.');
            }
    
            // Randomly generated id number
            $number = rand(10000, 99999);
    
            // Insert data into the user table
            $user = User::create([
                'user_id' => $number,
                'farm_name' => $request->farmname,
                'birthdate' => $request->birthdate,
                'password' => bcrypt($request->password),
            ]);
            
            $feedsStages = ['Farrowing', 'Weaner', 'Grower', 'Finisher', 'Breeder'];

            try {
                foreach ($feedsStages as $stage) {
                    $feed = new Feeds();
                    $feed->user_id = $user->id;
                    $feed->FeedsStage = $stage;
                    $feed->FeedsLeft = '0';
                    $feed->DaysLeft = '0';
                    $feed->save();
                    $feeds[] = $feed;
                }

                // Commit the transaction
                DB::commit();

                // Show response
                return response()->json([
                    'user' => $user,
                    'feeds' => $feeds,
                    'message' => "Account has been created successfully",
                    'status' => 'Success',
                ], 200);
            } catch (\Exception $e) {
                // If it throws an exception, the changes will be rolled back and will not be saved
                DB::rollBack();
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }
    
            // Commit the transaction
            DB::commit();
    
            // Show response
            return response()->json([
                'user' => $user,
                'message' => "Account has been created successfully",
                'status' => 'Success',
            ], 200);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => $e->validator->errors()->first(),
            ], 400);
        } catch (\Exception $e) {
            // If it throws an exception, the changes will be rolled back and will not be saved
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user' => 'required',
            'password' => 'required',
        ]);

        // Check if the user is registered
        $user = User::where('farm_name', $request->user)->first();;

                if (!$user || !Hash::check($request->password, $user->password)) {
                    return response([
                        'message' => 'Incorrect username or password. Please try again.'
                    ], 401); 
                        //401 status code means the user is unauthorized
                }
        
                // Generate a token if the user is authorized, this will be used to log in
                $token = $user->createToken($user->farm_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'message' => "Logged-in successfully!",
            'status' => 'Success',
        ], 200); 
    }

    public function logout(Request $request){
        // Delete the user's valid token for a logged-in user
        auth()->user()->tokens()->delete();
        return response()->json([
            'message' => 'User logged out.',
            'status' => 'Success',
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'farmname' => 'required|string|max:255',
            'birthdate' => 'required|date',
        ]);

        // Update user's farm name and birthdate
        $user->farm_name = $request->farmname;
        $user->birthdate = $request->birthdate;

        // Save the updated user
        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }


    public function create_pigs(Request $request) 
    {
        try {
            // Get the authenticated user's ID
            $user = User::find(auth('sanctum')->user()->id);

            $validateData = $request->validate([
                'PigName' => [
                    'required',
                    Rule::unique('pigs', 'pig_name')->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    })
                ],
                'PigBreed' => 'required',
                'Weight' => 'required',
                'Gender' => 'required',
                'PigStage' => 'required',
                'DateofBirth' => ['required', 'date', 'before_or_equal:' . now()->format('Y-m-d')],
                'DateofEntry' => 'required',
                'PigGroup' => 'required',

            ]);
    
            DB::beginTransaction();
    
            // Generate a random pigs_id
            $number = rand(10000, 99999);


            // If the pig's gender is female, create a status named "heat"
            if ($request->Gender === 'Female') {
                $status = 'heat';
                $HeatDate = Carbon::parse($request->DateofBirth)->addMonths(5);
                // Check if the HeatDate has passed
                while ($HeatDate->isPast()) {
                    // Add 3 weeks to HeatDate until it's in the future
                    $HeatDate->addWeeks(3);
                }
                // Insert data to pigs table
                $pigs = Pigs::create([
                'pigs_id' => $number,
                'user_id' => $user->id,
                'pig_name' => $request->PigName,
                'pig_breed' => $request->PigBreed,
                'weight' => $request->Weight,
                'gender' => $request->Gender,
                'pig_stage' => $request->PigStage,
                'date_of_birth' => $request->DateofBirth,
                'date_of_entry' => $request->DateofEntry,
                'pig_group' => $request->PigGroup,
                'pig_status' => $status,
                'status_date'=> $HeatDate,
                ]);
            }
            else{
                // Insert data to pigs table
                $pigs = Pigs::create([
                    'pigs_id' => $number,
                    'user_id' => $user->id,
                    'pig_name' => $request->PigName,
                    'pig_breed' => $request->PigBreed,
                    'weight' => $request->Weight,
                    'gender' => $request->Gender,
                    'pig_stage' => $request->PigStage,
                    'date_of_birth' => $request->DateofBirth,
                    'date_of_entry' => $request->DateofEntry,
                    'pig_group' => $request->PigGroup,
                    ]);
            }
            DB::commit();
            
            // Retrieve the feed record
            $feeds = Feeds::where('user_id', $user->id)
            ->where('FeedsStage', $request->PigStage)
            ->first();

        if (!$feeds) {
            return response()->json('No feed record found for the specified stage', 404);
        }
        
        // Calculate new values
        $newFeedsLeftValue = 0;
        $newDaysLeftValue = 0;
        $addedFeeds = 0;
        $pigsCount = Pigs::where('user_id', $user->id)
            ->where('pig_stage', $request->PigStage)
            ->count();

        if ($pigsCount === 0 || is_null($pigsCount)) {
            $feedsLeftValue = $feeds->FeedsLeft;
            $daysLeftValue = $feeds->DaysLeft;
            $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
            $newDaysLeftValue = 0;
        } else {
            $feedsLeftValue = $feeds->FeedsLeft;
            $daysLeftValue = $feeds->DaysLeft;

            switch ($request->PigStage) {
                case 'Farrowing':
                    // Calculate days to consume
                    $newFeedsLeftValue = $feedsLeftValue;
                    $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 1.81) * 3));
                    $newDaysLeftValue = $daysToConsume;
                    break;
                case 'Weaner':
                    // Calculate days to consume
                    $newFeedsLeftValue = $feedsLeftValue;
                    $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.200) * 3));
                    $newDaysLeftValue = $daysToConsume;
                    break;
                case 'Grower':
                case 'Finisher':
                    // Calculate days to consume
                    $newFeedsLeftValue = $feedsLeftValue;
                    $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.500) * 3));
                    $newDaysLeftValue = $daysToConsume;
                    break;
                case 'Breeder':
                    // Calculate days to consume
                    $newFeedsLeftValue = $feedsLeftValue;
                    $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.833) * 3));
                    $newDaysLeftValue = $daysToConsume;
                    break;
                default:
                    break;
            }
        }

        // Update the fields
        $feeds->DaysLeft = $newDaysLeftValue;
        $feeds->save();

        return response()->json([
            'pig' => $pigs,
            'message' => "Pig created successfully",
            'status' => 'Success',
        ], 200);
    } catch (ValidationException $e) {
        $errors = $e->validator->getMessageBag()->toArray();
        return response()->json([
            'message' => $errors,
        ], 400);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
    }
    
    
    public function farm_setup(Request $request){
        // Try statement to begin a database transaction
        try {
            $validateData = $request->validate([
                'income_categories' => ['nullable','unique:farm_setup,income_categories'],
                'expenses_categories' => ['nullable','unique:farm_setup,expenses_categories'],
                'pig_breeds' => ['nullable','unique:farm_setup,pig_breeds'],
                'feeds_type' => ['nullable','unique:farm_setup,feeds_type'],
                'pig_group' => ['nullable','unique:farm_setup,pig_group'],
            ]);

            // Check if the user is authenticated
            if (Auth::check()) {
                // Get the authenticated user's ID
                $user = Auth::user();
                $userId = $user->id;

                DB::beginTransaction();

                // Insert data into the FarmSetup table
                $farm_setup = FarmSetup::create([
                    'user_id' => $userId,
                    'income_categories' => $request->income_categories,
                    'expenses_categories' => $request->expenses_categories,
                    'pig_breeds' => $request->pig_breeds,
                    'feeds_type' => $request->feeds_type,
                    'pig_group' => $request->pig_group,
                ]);

                // Commit the transaction
                DB::commit();

                // Return a success response
                return response()->json([
                    'farm_setup' => $farm_setup,
                    'message' => 'Created successfully',
                    'status' => 'Success',
                ], 200);
            } else {
                // User is not authenticated
                return response()->json([
                    'message' => 'User is not authenticated',
                ], 401); // 401 Unauthorized status code
            }
        } catch (\Exception $e) {
            // If an exception is thrown, the changes will be rolled back
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], 400); // 400 Bad Request status code
        }
    }

    public function viewPigs(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
    
            // Get only pig names of the logged-in user
            $pigNames = pigs::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->pluck('pig_name')
                        ->toArray();
    
        return response()->json([
            'pig_names' => $pigNames,
        ], 200);
    }

    public function getPigDetails($pigName)
    {
        try {
            $user = Auth::user(); // Retrieve authenticated user

            $pigDetails = pigs::where('user_id', $user->id)
                            ->where('pig_name', $pigName)
                            ->first();

            if ($pigDetails) {
                return response()->json([
                    'pig_details' => $pigDetails,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Pig details not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching pig details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePigDetails(Request $request, $pigName)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'pig_name' => 'string|max:255',
            'pig_breed' => 'string|max:255',
            'weight' => 'integer',
            'gender' => 'string|in:Male,Female',
            'pig_stage' => 'string|max:255',
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->format('Y-m-d')],
            'date_of_entry' => 'date',
            'pig_group' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $pig = pigs::where('user_id', $user->id)
                ->where('pig_name', $pigName)
                ->first();

        if (!$pig) {
            return response()->json(['error' => 'Pig not found'], 404);
        }

        // Update each field individually
        if ($request->has('pig_name')) {
            $pig->pig_name = $request->input('pig_name');
        }
        if ($request->has('pig_breed')) {
            $pig->pig_breed = $request->input('pig_breed');
        }
        if ($request->has('weight')) {
            $pig->weight = $request->input('weight');
        }
        if ($request->has('gender')) {
            $pig->gender = $request->input('gender');
        }
        if ($request->has('pig_stage')) {
            $pig->pig_stage = $request->input('pig_stage');
        }
        if ($request->has('date_of_birth')) {
            $pig->date_of_birth = $request->input('date_of_birth');
        }
        if ($request->has('date_of_entry')) {
            $pig->date_of_entry = $request->input('date_of_entry');
        }
        if ($request->has('pig_group')) {
            $pig->pig_group = $request->input('pig_group');
        }

        if ($request->gender === 'Female') {
            $status = 'heat';
            $heatDate = Carbon::parse($request->date_of_birth)->addMonths(5);
            // Check if the heatDate has passed
            while ($heatDate->isPast()) {
                // Add 3 weeks to heatDate until it's in the future
                $heatDate->addWeeks(3);
            }
            $pig->pig_status = $status;
            $pig->status_date = $heatDate;
        }
        else{
            $pig->pig_status ='';
        }

        $pig->update();

        return response()->json(['message' => 'Pig details updated successfully']);
    }

    public function HeatPigs(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get pig names and status dates of the logged-in user
        $pigs = pigs::where('user_id', $user->id)
                    ->where('pig_status', 'heat')
                    ->orderBy('status_date', 'asc')
                    ->get(['pig_name', DB::raw('DATE(status_date) as status_date')]);

        return response()->json([
            'pigs' => $pigs,
        ], 200);
    }

    public function updatePigStatus(Request $request, $pigName)
    {
        // Get the authenticated user
        $user = Auth::user();

        $status = 'gestating';

        // Find the pig by name
        $pigs = pigs::where('user_id', $user->id)
                ->where('pig_name', $pigName)->first();

        // Check if pig exists
        if (!$pigs) {
            return response()->json(['error' => 'Pig not found'], 404);
        }
        // Calculate the new status date
        $newStatusDate = Carbon::now()->addMonths(3)->addWeeks(3)->addDays(3);

        // Update the pig status
        $pigs->update(['pig_status' => $status,
                       'status_date'=> $newStatusDate,]);


        return response()->json(['message' => 'Pig status updated successfully']);
    }

    public function updatePigStatus1(Request $request, $pigName)
    {
        // Get the authenticated user
        $user = Auth::user();

        $status = 'heat';

        // Find the pig by name
        $pigs = pigs::where('user_id', $user->id)
                ->where('pig_name', $pigName)->first();

        // Check if pig exists
        if (!$pigs) {
            return response()->json(['error' => 'Pig not found'], 404);
        }
        // Calculate the new status date
        $newStatusDate = Carbon::now()->addWeeks(3);

        // Update the pig status
        $pigs->update(['pig_status' => $status,
                       'status_date'=> $newStatusDate,]);

        return response()->json(['message' => 'Pig status updated successfully']);
    }

    public function GestatingPigs(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get pig names and status dates of the logged-in user
        $pigs = pigs::where('user_id', $user->id)
                    ->where('pig_status', 'gestating')
                    ->orderBy('status_date', 'asc')
                    ->get(['pig_name', DB::raw('DATE(status_date) as status_date')]);

        return response()->json([
            'pigs' => $pigs,
        ], 200);
    }
    
    public function viewProfile(Request $request)
    {
        // Get authenticated user
        $user = Auth::user();

        // Check if user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Return user details
        return response()->json([
            'farmname' => $user->farm_name,
            'birthdate' => $user->birthdate,
            'userid' => $user->user_id, // assuming user_id is actually 'id' field in the database
        ]);
    }

    public function ViewFeeds(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // Fetch all feeds data
        $feeds = Feeds::all()->map(function($feed) {
            return [
                'Stage' => $feed->FeedsStage,
                'Feeds Left' => $feed->FeedsLeft,
                'Days to Consume' => $feed->DaysLeft
            ];
        });
        
        return response()->json($feeds);
    }

    public function Transaction(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user

        $request->validate([
            'transactionType' => 'required|in:Income,Expense',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create a new transaction instance
            $transaction = new Transaction();
            
            // Associate the transaction with the authenticated user
            $transaction->user_id = $user->id;

            // Assign transaction data
            $transaction->transaction_type = $request->transactionType;
            $transaction->amount = $request->amount;
            $transaction->description = $request->description;

            // Save the transaction
            $transaction->save();

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Transaction saved successfully'], 200);
        } catch (\Exception $e) {
            // If an exception occurs, rollback the transaction
            DB::rollback();
            
            // Return an error response
            return response()->json(['error' => 'Failed to save transaction'], 500);
        }
    }
    public function Events(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user

        $request->validate([
            'name' => 'required',
            'date' => 'required|date',
            'description' => 'required',
        ]);

        // Begin a database transaction
        DB::beginTransaction();

        try {
            // Create a new Events instance
            $Events = new Events();

            // Associate the Events with the authenticated user
            $Events->user_id = $user->id;
            // Assign Events data
            $Events->name = $request->name;
            $Events->date = $request->date;
            $Events->description = $request->description;

            // Save the transaction
            $Events->save();

            // Commit the transaction if successful
            DB::commit();

            return response()->json(['message' => 'Event saved successfully'], 200);
        } catch (\Exception $e) {
            // If an exception occurs, rollback the transaction
            DB::rollback();

            // Log the error or handle it as required
            return response()->json(['message' => 'Failed to save event.'], 500);
        }
    }


    public function Reports(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user

        // Fetch all events associated with the authenticated user
        $events = Events::where('user_id', $user->id)->get();

        // Check if events were found
        if ($events->isEmpty()) {
            return response()->json(['message' => 'No events found.'], 404);
        }

        // Return the events in a JSON response
        return response()->json(['events' => $events], 200);
    }

    public function FarmSetup(Request $request)
    {   
        $request->validate([
            'categories' => 'required|in:group,breeds', // Modify this to accept dynamic categories
            'name' => 'required|string|max:255|unique:farm_setup,name',
        ]);

        $user = Auth::user();
        // Create a new record in the farm_setup table
        $farmSetup = new FarmSetup();
        $farmSetup->user_id = $user->id;
        $farmSetup->categories = $request->categories;
        $farmSetup->name = $request->name;
        $farmSetup->save();

        // Optionally, you can return a response to indicate success or failure
        return response()->json(['message' => 'Farm setup saved successfully'], 200);
    }

    public function ViewSetup(Request $request)
    {
        // Check if user is authenticated
        if (!$user = Auth::user()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the farm setups of the logged-in user grouped by categories
        $setups = FarmSetup::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->groupBy('categories')
                        ->map(function ($group, $category) {
                            return [
                                'category' => $category,
                                'names' => $group->pluck('name')->toArray()
                            ];
                        })
                        ->values(); // Reset the keys of the collection

        return response()->json([
            'setups' => $setups,
        ], 200);
    }
    
    public function deletePig($pigName) {

        $pig = pigs::where('pig_name', $pigName)->first();
        if ($pig) {
            $pig->delete();
            return response()->json(['message' => 'Pig deleted successfully']);
        } else {
            return response()->json(['error' => 'Pig not found'], 404);
        }
    }

    public function Revenue(Request $request)
    {
        $user = Auth::user();

        // Array to map month numbers to month names
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Fetch income and expense reports grouped by month
        $income = DB::table('transaction')
            ->select(DB::raw('SUM(amount) as total_income'),DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'))
            ->where('user_id', $user->id)
            ->where('transaction_type', 'Income')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        $expense = DB::table('transaction')
            ->select(DB::raw('SUM(amount) as total_expense'),DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'))
            ->where('user_id', $user->id)
            ->where('transaction_type', 'Expense')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        // Merge income and expense reports by month and year
        $reports = [];
        foreach ($income as $incomeItem) {
            $monthName = $monthNames[$incomeItem->month]; // Convert month number to name
            $reports[$incomeItem->year][$monthName] = [
                'total_income' => $incomeItem->total_income,
                'total_expense' => 0,
                'revenue' => 0,
                'transactions' => [] // Initialize transactions array
            ];
        }

        foreach ($expense as $expenseItem) {
            $monthName = $monthNames[$expenseItem->month]; // Convert month number to name
            $reports[$expenseItem->year][$monthName]['total_expense'] = $expenseItem->total_expense;
        }

        // Fill in missing months with empty arrays
        foreach ($monthNames as $month) {
            foreach ($reports as &$yearlyReports) {
                if (!isset($yearlyReports[$month])) {
                    $yearlyReports[$month] = [
                        'total_income' => 0,
                        'total_expense' => 0,
                        'revenue' => 0,
                        'transactions' => [] // Initialize transactions array
                    ];
                }
            }
        }

        // Get detailed transactions for each month
        foreach ($reports as $year => &$yearlyReports) {
            foreach ($yearlyReports as $monthName => &$report) {
                $transactions = DB::table('transaction')
                    ->select('amount', 'description', 'transaction_type')
                    ->where('user_id', $user->id)
                    ->whereMonth('created_at', array_search($monthName, $monthNames))
                    ->get();
                $report['transactions'] = $transactions;

                // Calculate revenue
                $revenue = $report['total_income'] - $report['total_expense'];

                $report['revenue'] = $revenue;
            }
        }

        return response()->json(['revenue_reports' => $reports]);
    }






    public function countPigsByBreed(Request $request)
    {
        try {
            // Get the authenticated user's ID
            $user = User::find(auth('sanctum')->user()->id);

            // Fetch the pig counts along with the pig names grouped by breed
            $pigsByBreed = Pigs::select('pig_breed', 'pig_name')
                ->where('user_id', $user->id)
                ->get()
                ->groupBy('pig_breed');

            // Prepare the result array
            $pigCounts = [];
            foreach ($pigsByBreed as $breed => $pigs) {
                $pigCounts[] = [
                    'breed' => $breed,
                    'total' => $pigs->count(),
                    'pig_names' => $pigs->pluck('pig_name')->toArray()
                ];
            }

            // Calculate total number of pigs
            $totalPigs = array_reduce($pigCounts, function ($carry, $item) {
                return $carry + $item['total'];
            }, 0);

            return response()->json([
                'pig_counts' => $pigCounts,
                'total_pigs' => $totalPigs, // Adding total count of pigs
                'message' => "Pig counts by breed fetched successfully",
                'status' => 'Success',
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function PigsReport(Request $request)
    {
        $user = User::find(Auth::id()); // Simplified the user retrieval
        $request->validate([
            'PigsName' => 'required|string',
            'Amount' => 'required|integer',
            'Description' => 'required|string',
        ]);

        $report = new PigsReport();
        $report->PigsName = $request->input('PigsName'); // Using input() method
        $report->Amount = $request->input('Amount');
        $report->Description = $request->input('Description');
        $report->user_id = $user->id; // Assuming user_id is the foreign key for the user
        $report->save();

        return response()->json(['message' => 'Pigs report saved successfully'], 200);
    }

    public function viewPigsReport(Request $request)
    {
        $user = Auth::user();

        // Array to map month numbers to month names
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Fetch income and expense reports grouped by month and year
        $income = DB::table('pigs_reports')
            ->select(DB::raw('SUM(Amount) as total_income'), DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'))
            ->where('user_id', $user->id)
            ->where('Description', 'Sold')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        $expense = DB::table('pigs_reports')
            ->select(DB::raw('SUM(Amount) as total_expense'), DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'))
            ->where('user_id', $user->id)
            ->where('Description', 'Deceased')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        // Fetch count of sold and deceased pigs grouped by month and year
        $soldCount = DB::table('pigs_reports')
            ->select(DB::raw('COUNT(*) as total_sold'), DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'))
            ->where('user_id', $user->id)
            ->where('Description', 'Sold')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        $deceasedCount = DB::table('pigs_reports')
            ->select(DB::raw('COUNT(*) as total_deceased'), DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'))
            ->where('user_id', $user->id)
            ->where('Description', 'Deceased')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        // Merge income and expense reports by month and year
        $reports = [];
        foreach ($income as $incomeItem) {
            $monthName = $monthNames[$incomeItem->month]; // Convert month number to name
            $reports[$incomeItem->year][$monthName] = [
                'total_income' => $incomeItem->total_income,
                'total_expense' => 0,
                'revenue' => 0,
                'sold_count' => 0,
                'deceased_count' => 0,
                'transactions' => [] // Initialize transactions array
            ];
        }

        foreach ($expense as $expenseItem) {
            $monthName = $monthNames[$expenseItem->month]; // Convert month number to name
            $reports[$expenseItem->year][$monthName]['total_expense'] = $expenseItem->total_expense;
        }

        foreach ($soldCount as $soldItem) {
            $monthName = $monthNames[$soldItem->month]; // Convert month number to name
            $reports[$soldItem->year][$monthName]['sold_count'] = $soldItem->total_sold;
        }

        foreach ($deceasedCount as $deceasedItem) {
            $monthName = $monthNames[$deceasedItem->month]; // Convert month number to name
            $reports[$deceasedItem->year][$monthName]['deceased_count'] = $deceasedItem->total_deceased;
        }

        // Fill in missing months with empty arrays
        foreach ($monthNames as $month) {
            foreach ($reports as &$yearlyReports) {
                if (!isset($yearlyReports[$month])) {
                    $yearlyReports[$month] = [
                        'total_income' => 0,
                        'total_expense' => 0,
                        'revenue' => 0,
                        'sold_count' => 0,
                        'deceased_count' => 0,
                        'transactions' => [] // Initialize transactions array
                    ];
                }
            }
        }

        // Get detailed transactions for each month
        foreach ($reports as $year => &$yearlyReports) {
            foreach ($yearlyReports as $monthName => &$report) {
                $transactions = DB::table('pigs_reports')
                    ->select('PigsName', 'Description', 'Amount')
                    ->where('user_id', $user->id)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', array_search($monthName, $monthNames))
                    ->get();
                $report['transactions'] = $transactions;

                // Calculate revenue
                $revenue = $report['total_income'] - $report['total_expense'];

                $report['revenue'] = $revenue;
            }
        }

        return response()->json(['revenue_reports' => $reports]);
    }


    public function changepassword(Request $request){
        try{
            // Validate user input
            $request->validate([
                'newpassword' => 'required|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$ %^&*-]).*$/',
            ]);
    
            DB::beginTransaction();
    
            // Updating user password
            $user = User::find(auth('sanctum')->user()->id);
    
            $user->password = Hash::make($request->newpassword); // Fixed typo: $request instead of $requet
            $user->save();
    
            /**
             * If try statement doesn't return exception,
             * the record will be saved.
             */
            DB::commit();
    
            return response()->json([
                'message' => "A new password has been set.",
                'status' => 'Success',
            ], 200);
    
        } catch(\Exception $e) {
            // If it throws an exception, the changes will rollback and will not be saved.
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
            // 400 status code means bad request
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'farm_name' => 'required',
            'birthdate' => 'required|date',
        ]);

        $user = User::where('farm_name', $request->farm_name)
                    ->where('birthdate', $request->birthdate)
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid farm name or birthdate'], 404);
        }

        // Generate a new password
        $newPassword = Str::random(10);

        // Update user's password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Send email with new password (you need to implement this)

        return response()->json(['message' => 'Password reset successful.', 'new_password' => $newPassword]);
    }

    public function viewFeedsInfo(Request $request)
    {
        $feedsInfo = FeedsInfo::all(['feed_type', 'feed_information', 'feed_price']);
        return response()->json($feedsInfo);
    }

    public function viewPigsInfo(Request $request)
    {
        $pigsInfo = PigsInfo::all(['Pig_breed', 'Pig_Info', 'Pig_Char']);
        return response()->json($pigsInfo);
    }

    public function StoreFeeds(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        $request->validate([
            'stage' => 'required',
            'Feeds' => 'required|regex:/^\d*(\.\d{1,2})?$/'
        ]);

        $feeds = feeds::where('user_id', $user->id)
            ->where('FeedsStage', $request->stage)
            ->first(); // Retrieve the feed record

        if (!$feeds) {
            // Handle case where no record is found
            return response()->json('No feed record found for the specified stage', 404);
        }

        $pigsCount = pigs::where('user_id', $user->id)
            ->where('pig_stage', $request->stage)
            ->count();

        $newFeedsLeftValue = 0; // Initialize the new value
        $newDaysLeftValue = 0; // Initialize the new days left value
        $addedFeeds = $request->Feeds;

        if ($pigsCount === 0 || is_null($pigsCount)) {
            $feedsLeftValue = $feeds->FeedsLeft;
            $daysLeftValue = $feeds->DaysLeft;

            $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
            $newDaysLeftValue = 0;
        } else {
            $feedsLeftValue = $feeds->FeedsLeft;
            $daysLeftValue = $feeds->DaysLeft;

            if ($request->stage == 'Farrowing') {
                $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 1.81) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Weaner') {
                $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.200) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Grower') {
                $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.500) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Finisher') {
                $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.5) * 3));
                $newDaysLeftValue = $daysToConsume;
            }  elseif ($request->stage == 'Breeder') {
                $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.833) * 3));
                $newDaysLeftValue = $daysToConsume;
            }
            error_log("Days to Consume: " . $daysToConsume);
        }

        // Create a new instance of the FeedUsage model
        $feedUsage = new FeedUsage();

        // Set the attributes of the model instance using the validated data
        $feedUsage->user_id = $user->id; // Assuming you have a user_id column in the feeds_usage table
        $feedUsage->stage = $request->input('stage');
        $feedUsage->feeds_added = $request->input('Feeds');

        // Save the model instance to the database
        $feedUsage->save();

        // Update the fields
        $feeds->FeedsLeft = $newFeedsLeftValue;
        $feeds->DaysLeft = $newDaysLeftValue;
        $feeds->save();

        return response()->json('Feed added successfully');
    }
    public function DoneFeeding(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        $request->validate([
            'stage' => 'required',
        ]);

        $feeds = feeds::where('user_id', $user->id)
            ->where('FeedsStage', $request->stage)
            ->first(); // Retrieve the feed record

        $pigsCount = pigs::where('user_id', $user->id)
            ->where('pig_stage', $request->stage)
            ->count();

        
        if (!$feeds) {
            // Handle case where no record is found
            return response()->json('No feed record found for the specified stage', 404);
        }
        
        $newFeedsLeftValue = 0; // Initialize the new value
        $newDaysLeftValue = 0; // Initialize the new days left value

        if ($pigsCount === 0 || is_null($pigsCount)) {
            $feedsLeftValue = $feeds->FeedsLeft;
            $daysLeftValue = $feeds->DaysLeft;

            $newFeedsLeftValue = $addedFeeds + $feedsLeftValue;
            $newDaysLeftValue = 0;
        } else {
            $feedsLeftValue = $feeds->FeedsLeft;
            $daysLeftValue = $feeds->DaysLeft;

            if ($request->stage == 'Farrowing') {
                $eatFeeds = ($pigsCount * 1.81);
                $newFeedsLeftValue = $feedsLeftValue - $eatFeeds;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 1.81) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Weaner') {
                $eatFeeds = ($pigsCount * 0.200);
                $newFeedsLeftValue = $feedsLeftValue - $eatFeeds;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.200) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Grower') {
                $eatFeeds = ($pigsCount * 0.500);
                $newFeedsLeftValue = $feedsLeftValue - $eatFeeds;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.500) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Finisher') {
                $eatFeeds = ($pigsCount * 0.500);
                $newFeedsLeftValue = $feedsLeftValue - $eatFeeds;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.500) * 3));
                $newDaysLeftValue = $daysToConsume;
            } elseif ($request->stage == 'Breeder') {
                $eatFeeds = ($pigsCount * 0.833);
                $newFeedsLeftValue = $feedsLeftValue - $eatFeeds;
                $daysToConsume = ($newFeedsLeftValue / (($pigsCount * 0.833) * 3));
                $newDaysLeftValue = $daysToConsume;
            }
            error_log("Days to Consume: " . $daysToConsume);
        }

        // Check if FeedsLeft is less than 0
        if ($newFeedsLeftValue < 0) {
            return response()->json('Feeds left cannot be negative', 400);
        }

        // Update the fields
        $feeds->FeedsLeft = $newFeedsLeftValue;
        $feeds->DaysLeft = $newDaysLeftValue;
        $feeds->save();

        // Create a new instance of the FeedUsage model
        $feedUsage = new FeedUsage();

        // Set the attributes of the model instance using the validated data
        $feedUsage->user_id = $user->id; // Assuming you have a user_id column in the feeds_usage table
        $feedUsage->stage = $request->input('stage');
        $feedUsage->usage = $eatFeeds;

        // Save the model instance to the database
        $feedUsage->save();

        return response()->json('Feeding done successfully');
    }

    public function feedReport(Request $request)
    {
        $user = Auth::user();
        $feeds = FeedUsage::where('user_id', $user->id)->select('stage', 'feeds_added', 'usage', 'created_at')->get();

        // Array to map month numbers to month names
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Initialize arrays to hold the total sum for each stage, grouped by month and year
        $totalFeeds = [];
        $totalUsage = [];

        // Iterate over the feeds to calculate totals
        foreach ($feeds as $feed) {
            $stage = $feed->stage;
            $month = $monthNames[$feed->created_at->month];
            $year = $feed->created_at->year;

            // Initialize the total sum for the current stage if not already set
            if (!isset($totalFeeds[$year][$month][$stage])) {
                $totalFeeds[$year][$month][$stage] = 0;
                $totalUsage[$year][$month][$stage] = 0;
            }

            // Add feeds_added and usage to the total sum for the current stage
            $totalFeeds[$year][$month][$stage] += $feed->feeds_added;
            $totalUsage[$year][$month][$stage] += $feed->usage;
        }

        // Prepare the result array
        $result = [];

        // Iterate over the totals to build the result array
        foreach ($totalFeeds as $year => $yearData) {
            foreach ($yearData as $month => $monthData) {
                // Display year and month only once
                $result[] = [
                    'year' => $year,
                    'month' => $month,
                ];
                foreach ($monthData as $stage => $totalFeed) {
                    $result[] = [
                        'stage' => $stage,
                        'total_feeds' => $totalFeed,
                        'total_usage' => $totalUsage[$year][$month][$stage]
                    ];
                }
            }
        }

        return response()->json($result);
    }






}