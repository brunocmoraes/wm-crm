<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Quote\QuoteDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Attribute\Http\Requests\AttributeForm;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Quote\Repositories\QuoteRepository;

class QuoteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected QuoteRepository $quoteRepository,
        protected LeadRepository $leadRepository
    ) {
        request()->request->add(['entity_type' => 'quotes']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(QuoteDataGrid::class)->process();
        }

        return view('admin::quotes.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $lead = $this->leadRepository->find(request('id'));

        return view('admin::quotes.create', compact('lead'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(AttributeForm $request)
    {
        Event::dispatch('quote.create.before');

        $quote = $this->quoteRepository->create(request()->all());

        if (request('lead_id')) {
            $lead = $this->leadRepository->find(request('lead_id'));

            $lead->quotes()->attach($quote->id);
        }

        Event::dispatch('quote.create.after', $quote);

        session()->flash('success', trans('admin::app.quotes.index.create-success'));

        return redirect()->route('admin.quotes.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $quote = $this->quoteRepository->findOrFail($id);

        return view('admin::quotes.edit', compact('quote'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AttributeForm $request, $id)
    {
        Event::dispatch('quote.update.before', $id);

        $quote = $this->quoteRepository->update(request()->all(), $id);

        $quote->leads()->detach();

        if (request('lead_id')) {
            $lead = $this->leadRepository->find(request('lead_id'));

            $lead->quotes()->attach($quote->id);
        }

        Event::dispatch('quote.update.after', $quote);

        session()->flash('success', trans('admin::app.quotes.index.update-success'));

        return redirect()->route('admin.quotes.index');
    }

    /**
     * Search quote results
     *
     * @return \Illuminate\Http\Response
     */
    public function search()
    {
        $results = $this->quoteRepository->findWhere([
            ['subject', 'like', '%'.urldecode(request()->input('query')).'%'],
        ]);

        return response()->json($results);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->quoteRepository->findOrFail($id);

        try {
            Event::dispatch('quote.delete.before', $id);

            $this->quoteRepository->delete($id);

            Event::dispatch('quote.delete.after', $id);

            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Mass Delete the specified resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(MassDestroyRequest $request)
    {
        dd($request->all());

        foreach (request('rows') as $quoteId) {
            Event::dispatch('quote.delete.before', $quoteId);

            $this->quoteRepository->delete($quoteId);

            Event::dispatch('quote.delete.after', $quoteId);
        }

        return response()->json([
            'message' => trans('admin::app.response.destroy-success', ['name' => trans('admin::app.quotes.index.title')]),
        ]);
    }

    /**
     * Print and download the for the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        $quote = $this->quoteRepository->findOrFail($id);

        return PDF::loadHTML(view('admin::quotes.pdf', compact('quote'))->render())
            ->setPaper('a4')
            ->download('Quote_'.$quote->subject.'.pdf');
    }
}
