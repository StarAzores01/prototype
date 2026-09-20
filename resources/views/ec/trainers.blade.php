@extends('layouts.ec')

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">
      PAThrive
      <i class="fas fa-chevron-right"></i>
      <span>Project Leaders</span>
    </div>

    <h1>Project Leaders</h1>
    <p>Manage approved Project Leaders and their system access</p>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button
      type="button"
      class="btn btn-outline"
      onclick="openModal('addWhitelist')"
    >
      <i class="fas fa-list-check"></i>
      Add to Approved List
    </button>

    <button
      type="button"
      class="btn btn-primary"
      onclick="openModal('createTrainerAccount')"
    >
      <i class="fas fa-user-plus"></i>
      Create Account Directly
    </button>
  </div>
</div>


{{-- ============================================================
     ALERTS
============================================================ --}}

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">
    <i class="fas fa-circle-check"></i>
    {{ session('success') }}
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:16px">
    <i class="fas fa-circle-exclamation"></i>
    {{ session('error') }}
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:16px">
    <i class="fas fa-circle-exclamation"></i>

    <div>
      @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  </div>
@endif


{{-- ============================================================
     APPROVED PROJECT LEADERS / WHITELIST
============================================================ --}}

<div class="card" style="margin-bottom:24px">

  <div class="card-header">
    <div>
      <div class="card-title">
        <i class="fas fa-circle" style="font-size:6px"></i>
        Approved Project Leaders List
      </div>

      <div class="card-subtitle">
        Only these Project Leaders can register an account in the system
      </div>
    </div>
  </div>

  <div class="table-wrap">

    <table>

      <thead>
        <tr>
          <th>Name</th>
          <th>Assigned ID</th>
          <th>Specialization</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>

      @foreach($whitelist as $w)

        <tr>

          <td>
            <strong>
              {{ $w->first_name }} {{ $w->last_name }}
            </strong>
          </td>

          <td>

            @if($w->id_number)

              <span
                style="
                  font-size:12px;
                  font-weight:700;
                  color:var(--blue-primary);
                  background:var(--blue-soft);
                  padding:3px 8px;
                  border-radius:6px;
                "
              >
                {{ $w->id_number }}
              </span>

            @else

              <span style="font-size:12px;color:var(--gray-400)">
                —
              </span>

            @endif

          </td>

          <td style="font-size:12px;color:var(--gray-600)">
            {{ $w->specialization }}
          </td>

          <td>

            @if($w->is_registered)

              <span class="badge badge-active">
                Registered
              </span>

            @else

              <span class="badge badge-pending">
                Not yet registered
              </span>

            @endif

          </td>

          <td>

            @if(!$w->is_registered)

  <form
    method="POST"
    action="{{ route('ec.trainers.store') }}"
    style="display:inline"
    onsubmit="return confirm('Remove {{ addslashes($w->first_name) }} {{ addslashes($w->last_name) }} from the approved list?')"
  >
    @csrf

    <input
      type="hidden"
      name="action"
      value="remove_whitelist"
    />

    <input
      type="hidden"
      name="whitelist_id"
      value="{{ $w->id }}"
    />

    <button
      type="submit"
      class="btn btn-sm btn-danger"
    >
      <i class="fas fa-trash"></i>
      Remove
    </button>
  </form>

@else

  <span style="font-size:12px;color:var(--gray-400)">
    Account exists
  </span>

@endif

          </td>

        </tr>

      @endforeach


      @if($whitelist->isEmpty())

        <tr>
          <td
            colspan="5"
            style="
              text-align:center;
              padding:32px;
              color:var(--gray-400)
            "
          >
            No approved Project Leaders yet.
            Click "Add to Approved List" to add one.
          </td>
        </tr>

      @endif

      </tbody>

    </table>

  </div>

</div>


{{-- ============================================================
     REGISTERED PROJECT LEADER ACCOUNTS
============================================================ --}}

<div class="card">

  <div class="card-header">

    <div>
      <div class="card-title">
        Registered Project Leader Accounts
      </div>

      <div class="card-subtitle">
        {{ $trainers->count() }} accounts
      </div>
    </div>

  </div>


  <div class="card-body" style="padding-bottom:0">

    <form
      method="GET"
      action="{{ route('ec.trainers') }}"
      class="filter-row"
    >

      <div class="search-box">

        <i class="fas fa-magnifying-glass"></i>

        <input
          type="text"
          name="q"
          value="{{ $q }}"
          placeholder="Search by name or email…"
        />

      </div>

      <button
        type="submit"
        class="btn btn-primary btn-sm"
      >
        <i class="fas fa-magnifying-glass"></i>
        Search
      </button>

      @if($q)

        <a
          href="{{ route('ec.trainers') }}"
          class="btn btn-ghost btn-sm"
        >
          Clear
        </a>

      @endif

    </form>

  </div>


  <div class="table-wrap">

    <table>

      <thead>
        <tr>
          <th>Name</th>
          <th>Project Leader ID</th>
          <th>Email</th>
          <th>Position</th>
          <th>Activities</th>
          <th>Date Created</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>

      @php
        $colors = [
          '#1A56DB',
          '#10B981',
          '#F59E0B',
          '#EF4444',
          '#8B5CF6',
          '#06B6D4'
        ];
      @endphp


      @forelse($trainers as $tr)

        @php

          $trInitials =
            strtoupper(
              mb_substr($tr->first_name, 0, 1) .
              mb_substr($tr->last_name, 0, 1)
            );

          $color =
            $colors[$tr->id % count($colors)];

          $trId =
            $trainerIds[$tr->id] ?? '—';

        @endphp


        <tr>

          <td>

            <div
              style="
                display:flex;
                align-items:center;
                gap:10px
              "
            >

              <div
                class="participant-avatar"
                style="background:{{ $color }}"
              >
                {{ $trInitials }}
              </div>

              <div>

                <div
                  style="
                    font-weight:600;
                    font-size:13px
                  "
                >
                  {{ $tr->first_name }}
                  {{ $tr->last_name }}
                </div>

              </div>

            </div>

          </td>


          <td>

            <span
              style="
                font-size:12px;
                font-weight:700;
                color:var(--blue-primary);
                background:var(--blue-soft);
                padding:3px 8px;
                border-radius:6px
              "
            >
              {{ $trId }}
            </span>

          </td>


          <td style="font-size:12px">
            {{ $tr->email ?? '—' }}
          </td>


          <td
            style="
              font-size:12px;
              color:var(--gray-600)
            "
          >
            {{ $tr->position ?? '—' }}
          </td>


          <td>
            <strong>
              {{ $tr->training_count }}
            </strong>
          </td>


          <td
            style="
              font-size:12px;
              color:var(--gray-400)
            "
          >
            {{ $tr->created_at?->format('M d, Y') ?? '—' }}
          </td>


          <td>

            <span
              class="
                badge
                {{ $tr->is_active
                    ? 'badge-active'
                    : 'badge-inactive'
                }}
              "
            >
              {{ $tr->is_active
                  ? 'Active'
                  : 'Inactive'
              }}
            </span>

          </td>


          <td>

            <div class="action-btns">

              <button
                type="button"
                class="btn btn-sm btn-outline"
                onclick='openEditModal(@json($tr))'
              >
                <i class="fas fa-pen"></i>
                Edit
              </button>


              <form
                method="POST"
                action="{{ route('ec.trainers.store') }}"
                style="display:inline"
              >

                @csrf

                <input
                  type="hidden"
                  name="action"
                  value="toggle"
                />

                <input
                  type="hidden"
                  name="user_id"
                  value="{{ $tr->id }}"
                />

                <button
                  type="submit"
                  class="
                    btn
                    btn-sm
                    {{ $tr->is_active
                        ? 'btn-danger'
                        : 'btn-outline'
                    }}
                  "
                  onclick="return confirm('{{ $tr->is_active ? 'Disable' : 'Enable' }} access for {{ addslashes($tr->first_name) }}?')"
                >

                  <i
                    class="
                      fas
                      {{ $tr->is_active
                          ? 'fa-ban'
                          : 'fa-check-circle'
                      }}
                    "
                  ></i>

                  {{ $tr->is_active
                      ? 'Disable'
                      : 'Enable'
                  }}

                </button>

              </form>

            </div>

          </td>

        </tr>


      @empty

        <tr>

          <td
            colspan="8"
            style="
              text-align:center;
              padding:40px;
              color:var(--gray-400)
            "
          >
            No registered Project Leader accounts yet.
          </td>

        </tr>

      @endforelse

      </tbody>

    </table>

  </div>

</div>


{{-- ============================================================
     MODAL — ADD TO APPROVED LIST
============================================================ --}}

<div
  class="modal-overlay"
  id="modal-addWhitelist"
>

  <div class="modal">

    <div class="modal-header">

      <h2>
        <i class="fas fa-plus"></i>
        Add Approved Project Leader
      </h2>

      <button
        type="button"
        class="modal-close"
        onclick="closeModal('addWhitelist')"
      >
        <i class="fas fa-xmark"></i>
      </button>

    </div>


    <form
      method="POST"
      action="{{ route('ec.trainers.store') }}"
    >

      @csrf

      <input
        type="hidden"
        name="action"
        value="add_whitelist"
      />


      <div class="modal-body">

        <div
          class="alert alert-info"
          style="margin-bottom:16px"
        >
          <i class="fas fa-circle-info"></i>

          Adding a Project Leader here allows them
          to create their own account through the
          Project Leader registration page.
        </div>


        <div class="form-row">

          <div class="form-group">

            <label class="form-label">
              First Name *
            </label>

            <input
              type="text"
              name="first_name"
              class="form-control"
              placeholder="e.g. Juan"
              required
            />

          </div>


          <div class="form-group">

            <label class="form-label">
              Last Name *
            </label>

            <input
              type="text"
              name="last_name"
              class="form-control"
              placeholder="e.g. Dela Cruz"
              required
            />

          </div>

        </div>


        <div class="form-group">

          <label class="form-label">
            Specialization *
          </label>

          <select
            name="specialization"
            class="form-control"
            required
          >

            <option value="">
              — Select Area —
            </option>

            @foreach($areas as $a)

              <option value="{{ $a }}">
                {{ $a }}
              </option>

            @endforeach

          </select>

        </div>

      </div>


      <div class="modal-footer">

        <button
          type="button"
          class="btn btn-outline"
          onclick="closeModal('addWhitelist')"
        >
          Cancel
        </button>

        <button
          type="submit"
          class="btn btn-primary"
        >
          <i class="fas fa-check"></i>
          Add to Approved Project Leaders List
        </button>

      </div>

    </form>

  </div>

</div>


{{-- ============================================================
     MODAL — CREATE ACCOUNT DIRECTLY
============================================================ --}}

<div
  class="modal-overlay"
  id="modal-createTrainerAccount"
>

  <div class="modal">

    <div class="modal-header">

      <h2>
        <i class="fas fa-user-plus"></i>
        Create Project Leader Account
      </h2>

      <button
        type="button"
        class="modal-close"
        onclick="closeModal('createTrainerAccount')"
      >
        <i class="fas fa-xmark"></i>
      </button>

    </div>


    <form
      method="POST"
      action="{{ route('ec.trainers.store') }}"
    >

      @csrf

      <input
        type="hidden"
        name="action"
        value="create_account_direct"
      />


      <div class="modal-body">

        <div
          class="alert alert-info"
          style="margin-bottom:16px"
        >

          <i class="fas fa-circle-info"></i>

          This creates the Project Leader's account
          immediately. PAThrive will generate a
          temporary password and send the login
          credentials to their email address.

          They will be required to create a new
          password after their first login.

        </div>


        <div class="form-row">

          <div class="form-group">

            <label class="form-label">
              First Name *
            </label>

            <input
              type="text"
              name="first_name"
              class="form-control"
              placeholder="e.g. Juan"
              value="{{ old('action') === 'create_account_direct' ? old('first_name') : '' }}"
              required
            />

          </div>


          <div class="form-group">

            <label class="form-label">
              Last Name *
            </label>

            <input
              type="text"
              name="last_name"
              class="form-control"
              placeholder="e.g. Dela Cruz"
              value="{{ old('action') === 'create_account_direct' ? old('last_name') : '' }}"
              required
            />

          </div>

        </div>


        <div class="form-row">

          <div class="form-group">

            <label class="form-label">
              Username *
            </label>

            <input
              type="text"
              name="username"
              class="form-control"
              placeholder="e.g. juandelacruz"
              value="{{ old('action') === 'create_account_direct' ? old('username') : '' }}"
              required
            />

          </div>


          <div class="form-group">

            <label class="form-label">
              Email *
            </label>

            <input
              type="email"
              name="email"
              class="form-control"
              placeholder="name@example.com"
              value="{{ old('action') === 'create_account_direct' ? old('email') : '' }}"
              required
            />

          </div>

        </div>


        <div class="form-group">

          <label class="form-label">
            Position *
          </label>

          <select
            name="position"
            class="form-control"
            required
          >

            <option value="">
              — Select Position —
            </option>

            <option
              value="Professor"
              @selected(
                old('action') === 'create_account_direct'
                && old('position') === 'Professor'
              )
            >
              Professor
            </option>

            <option
              value="Assistant Professor"
              @selected(
                old('action') === 'create_account_direct'
                && old('position') === 'Assistant Professor'
              )
            >
              Assistant Professor
            </option>

            <option
              value="Instructor"
              @selected(
                old('action') === 'create_account_direct'
                && old('position') === 'Instructor'
              )
            >
              Instructor
            </option>

          </select>

        </div>

      </div>


      <div class="modal-footer">

        <button
          type="button"
          class="btn btn-outline"
          onclick="closeModal('createTrainerAccount')"
        >
          Cancel
        </button>

        <button
          type="submit"
          class="btn btn-primary"
        >
          <i class="fas fa-paper-plane"></i>
          Create Account & Send Credentials
        </button>

      </div>

    </form>

  </div>

</div>


{{-- ============================================================
     MODAL — EDIT PROJECT LEADER
============================================================ --}}

<div
  class="modal-overlay"
  id="modal-editTrainer"
>

  <div class="modal">

    <div class="modal-header">

      <h2>
        <i class="fas fa-pen"></i>
        Edit Project Leader
      </h2>

      <button
        type="button"
        class="modal-close"
        onclick="closeModal('editTrainer')"
      >
        <i class="fas fa-xmark"></i>
      </button>

    </div>


    <form
      method="POST"
      action="{{ route('ec.trainers.store') }}"
    >

      @csrf

      <input
        type="hidden"
        name="action"
        value="edit_trainer"
      />

      <input
        type="hidden"
        name="user_id"
        id="edit_user_id"
      />


      <div class="modal-body">

        <div class="form-row">

          <div class="form-group">

            <label class="form-label">
              First Name *
            </label>

            <input
              type="text"
              name="first_name"
              id="edit_first_name"
              class="form-control"
              required
            />

          </div>


          <div class="form-group">

            <label class="form-label">
              Last Name *
            </label>

            <input
              type="text"
              name="last_name"
              id="edit_last_name"
              class="form-control"
              required
            />

          </div>

        </div>


        <div class="form-row">

          <div class="form-group">

            <label class="form-label">
              Email *
            </label>

            <input
              type="email"
              name="email"
              id="edit_email"
              class="form-control"
              required
            />

          </div>


          <div class="form-group">

            <label class="form-label">
              ID Number
            </label>

            <input
              type="text"
              name="id_number"
              id="edit_id_number"
              class="form-control"
            />

          </div>

        </div>


        <div class="form-group">

          <label class="form-label">
            Position
          </label>

          <select
            name="position"
            id="edit_position"
            class="form-control"
          >

            <option value="Professor">
              Professor
            </option>

            <option value="Assistant Professor">
              Assistant Professor
            </option>

            <option value="Instructor">
              Instructor
            </option>

          </select>

        </div>

      </div>


      <div class="modal-footer">

        <button
          type="button"
          class="btn btn-outline"
          onclick="closeModal('editTrainer')"
        >
          Cancel
        </button>

        <button
          type="submit"
          class="btn btn-primary"
        >
          <i class="fas fa-check"></i>
          Save Changes
        </button>

      </div>

    </form>

  </div>

</div>


<script>

function openEditModal(tr) {

  document.getElementById('edit_user_id').value =
    tr.id;

  document.getElementById('edit_first_name').value =
    tr.first_name;

  document.getElementById('edit_last_name').value =
    tr.last_name;

  document.getElementById('edit_email').value =
    tr.email || '';

  document.getElementById('edit_id_number').value =
    tr.id_number || '';

  document.getElementById('edit_position').value =
    tr.position || 'Instructor';

  openModal('editTrainer');
}


/*
|--------------------------------------------------------------------------
| Re-open direct-account modal if validation failed
|--------------------------------------------------------------------------
|
| Laravel redirects back with the old form input after a validation error.
| This lets the EC immediately see the same form instead of having to click
| "Create Account Directly" again.
|
*/

document.addEventListener('DOMContentLoaded', function () {

  @if(old('action') === 'create_account_direct' && $errors->any())
    openModal('createTrainerAccount');
  @elseif(old('action') === 'add_whitelist' && $errors->any())
    openModal('addWhitelist');
  @endif

});

</script>

@endsection
