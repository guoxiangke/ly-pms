<div class="m-8 px-4 sm:px-6 lg:px-8">
    <div class="mt-8">
          <div class="max-w-full overflow-x-auto max-h-screen overflow-y-scroll inline-block align-middle">
            <table class="min-w-full border-separate border-spacing-0">
              <thead class="">
                <tr class="divide-x divide-gray-200">
                  <th scope="col" class="sticky bg-gray-50 top-0 z-10  py-3.5 pl-4 pr-4 text-left text-sm font-semibold text-gray-100 sm:pl-0">{{ now()->format("md D") }}</th>
                  @for ($i = -$before; $i < $after; $i++)
                    <th scope="col" class="sticky bg-gray-50 top-0 z-10 px-3 py-3.5 text-left text-sm font-semibold text-{{$i==0?'red':'green'}}-600">{{ now()->addDays($i)->format("md D") }}</th>
                  @endfor
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($lyMetas as $lyMeta)
                    <tr class="divide-x divide-gray-200 even:bg-gray-50">
                      <td class="flex items-center justify-center whitespace-nowrap text-sm font-medium text-gray-900 sm:pl-0"><div class="flex-shrink-0">
                            <img class="h-12 w-12" src="{{$lyMeta->cover}}" alt="{{$lyMeta->name}}">
                          </div></td>

                      @for ($i = -$before; $i < $after; $i++)
                    @php 
                        if(isset($lyItems[$lyMeta->id]))
                            $lyItemsByCid = $lyItems[$lyMeta->id]->keyBy('play_at');
                        else
                            $lyItemsByCid = [];
                    @endphp
                          <td class="whitespace-nowrap px-3 py-5 text-sm text-gray-500">
                            <div class="flex items-center justify-center ">
                            @if(isset($lyItemsByCid[now()->addDays($i)->startOfDay()->format('Y-m-d H:i:s')]))
                            
                            <a href="{{$lyItemsByCid[now()->addDays($i)->startOfDay()->format('Y-m-d H:i:s')]->path}}">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"></path>
                            </svg>
                            </a>
                            @else
                            <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                            </svg>
                            @endif
                            </div>

                          </td>
                      @endfor
                    </tr>
                @endforeach

              </tbody>
            </table>
          </div>
  </div>
</div>

