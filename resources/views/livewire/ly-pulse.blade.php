<div class="">
          <div class="max-w-full overflow-x-auto max-h-screen overflow-y-scroll align-middle">
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
                            <img class="h-12 w-12 hidden" src="{{$lyMeta->cover}}" alt="{{$lyMeta->name}}">
                            {{$lyMeta->code}}
                          </div></td>

                      @for ($i = -$before; $i < $after; $i++)
                        @php 
                            if(isset($lyItems[$lyMeta->id]))
                                $lyItemsByCid = $lyItems[$lyMeta->id]->keyBy('play_at');
                            else
                                $lyItemsByCid = [];
                        @endphp
                          <td class="whitespace-nowrap px-1 py-mt-4 text-sm text-gray-500">
                            <div class="flex items-center justify-center ">
                              @php
                                $date = now()->addDays($i)->startOfDay();
                                $index = $date->format('Y-m-d H:i:s');
                                $lyItem = $lyItemsByCid[$index]??false;
                              @endphp
                            @if($lyItem)
                            <div class="flex flex-col items-center">
                              <div class="">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 24 24" fill="#4caf50" aria-hidden="true">
                                 <path d="M 12 4 C 9.709 4 7.5608125 5.1379062 6.2578125 7.0039062 C 2.7848125 7.1319063 5.9211895e-16 9.997 0 13.5 C 0 17.084 2.916 20 6.5 20 L 7.5546875 20 C 7.2106875 19.41 7 18.732 7 18 L 6.5 18 C 4.019 18 2 15.981 2 13.5 C 2 11.019 4.019 9 6.5 9 L 7.359375 9.046875 L 7.6679688 8.5136719 C 8.5599687 6.9626719 10.22 6 12 6 C 14.472 6 16.544641 7.7728437 16.931641 10.214844 L 17.082031 11.175781 L 18.181641 11.03125 C 18.286641 11.01725 18.391 11 18.5 11 C 20.43 11 22 12.57 22 14.5 C 22 16.43 20.43 18 18.5 18 L 15 18 C 15 18.732 14.789312 19.41 14.445312 20 L 18.5 20 C 21.532 20 24 17.533 24 14.5 C 24 11.536 21.643078 9.1119062 18.705078 9.0039062 C 17.839078 6.0559063 15.149 4 12 4 z M 11 12 L 11 16 A 2 2 0 0 0 9 18 A 2 2 0 0 0 11 20 A 2 2 0 0 0 13 18 L 13 14 L 15 14 L 15 12 L 11 12 z"/>
                                </svg>
                              </div>
                              <div class="pt-1">
                                @if($lyItem->description)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 48 48" stroke-width="1.5" stroke="" aria-hidden="true">
                                  <path fill="#c8e6c9" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"/><path fill="#4caf50" d="M34.586,14.586l-13.57,13.586l-5.602-5.586l-2.828,2.828l8.434,8.414l16.395-16.414L34.586,14.586z"/>
                                </svg>
                                @else
                                  <svg class="h-5 w-5" fill="none" viewBox="0 0 48 48" stroke-width="1.5" stroke="" aria-hidden="true">
                                    <path fill="#be123c" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"/><path fill="#fff" d="M34.586,14.586l-13.57,13.586l-5.602-5.586l-2.828,2.828l8.434,8.414l16.395-16.414L34.586,14.586z"/>
                                  </svg>
                                @endif

                              </div>
                            </div>

                            @else
                              @if($lyMeta->hasItemByDate($date))
                               <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                                </svg>
                              @else
                              ---
                              @endif
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