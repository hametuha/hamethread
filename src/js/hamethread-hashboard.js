/*!
 * Thread list
 *
 * @handle hamethread-hashboard
 * @deps wp-element, wp-api-fetch, wp-i18n, hb-components-loading, hb-components-pagination, hb-plugins-toast
 */

const { useState, useEffect, useMemo, createRoot } = wp.element;
const { __, _x } = wp.i18n;
const apiFetch = wp.apiFetch;
const { LoadingIndicator, Pagination } = hb.components;
const { toast } = hb.plugins;

const ThreadList = () => {
	const [ threads, setThreads ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ total, setTotal ] = useState( 0 );
	const [ current, setCurrent ] = useState( 1 );
	const [ search, setSearch ] = useState( '' );
	const [ resolved, setResolved ] = useState( 0 );
	const [ isPrivate, setIsPrivate ] = useState( false );

	const status = useMemo( () => {
		return isPrivate ? 'private' : 'private,publish';
	}, [ isPrivate ] );

	const fetchThreads = ( page ) => {
		if ( loading ) {
			return;
		}
		setLoading( true );

		const params = new URLSearchParams( {
			s: search,
			page,
			status,
			resolved,
		} );

		apiFetch( {
			path: `/hamethread/v1/threads/me?${ params.toString() }`,
			parse: false,
		} )
			.then( ( response ) => {
				const totalPages = parseInt(
					response.headers.get( 'X-WP-Max-Page' ),
					10
				);
				setTotal( totalPages || 0 );
				return response.json();
			} )
			.then( ( data ) => {
				setCurrent( page );
				setThreads( data );
			} )
			.catch( ( error ) => {
				toast( __( 'Failed to fetch threads:', 'hamethread' ) + error.message, {
					type: 'danger',
					icon: 'error',
				} );
			} )
			.finally( () => {
				setLoading( false );
			} );
	};

	useEffect( () => {
		fetchThreads( 1 );
	}, [] );

	const handleUpdate = () => {
		fetchThreads( 1 );
	};

	const handlePageChange = ( page ) => {
		fetchThreads( page );
	};

	const formatDate = ( dateString, format = 'short' ) => {
		const date = new Date( dateString );
		const options =
			format === 'long'
				? {
						year: 'numeric',
						month: 'long',
						day: 'numeric',
						hour: '2-digit',
						minute: '2-digit',
				  }
				: { year: 'numeric', month: 'long', day: 'numeric' };
		return date.toLocaleDateString( undefined, options );
	};

	return (
		<div className="hamethread-list-wrapper">
			<header className="hamethread-list-meta">
				<form
					className="hamethread-list-meta-form d-flex gap-3 flex-wrap align-items-center"
					onSubmit={ ( e ) => {
						e.preventDefault();
						handleUpdate();
					} }
				>
					<div className="form-group">
						<select
							value={ resolved }
							className="form-select"
							onChange={ ( e ) => setResolved( parseInt( e.target.value, 10 ) ) }
						>
							<option value="0">{ __( 'All Status', 'hamethread' ) }</option>
							<option value="1">{ __( 'Resolved', 'hamethread' ) }</option>
							<option value="-1">{ __( 'Not Resolved', 'hamethread' ) }</option>
						</select>
					</div>
					<div className="form-group">
						<div className="form-check">
							<input
								className="form-check-input"
								type="checkbox"
								checked={ isPrivate }
								id="hamethread-private"
								onChange={ ( e ) => setIsPrivate( e.target.checked ) }
							/>
							<label className="form-check-label" htmlFor="hamethread-private">
								{ __( 'Only private', 'hamethread' ) }
							</label>
						</div>
					</div>
					<div className="form-group">
						<input
							type="search"
							value={ search }
							className="form-control"
							placeholder={ __( 'Search keyword...', 'hamethread' ) }
							aria-label={ __( 'Search keyword...', 'hamethread' ) }
							onChange={ ( e ) => setSearch( e.target.value ) }
						/>
					</div>
					<div className="form-group">
						<button className="btn btn-primary" type="submit">
							{ __( 'Filter', 'hamethread' ) }
						</button>
					</div>
				</form>
			</header>

			<div className="hamethread-list-body mt-3">
				{ threads.length > 0 ? (
					<>
						<ul className="hamethread-list-items list-group">
							{ threads.map( ( thread ) => (
								<li key={ thread.id } className="hamethread-list-item list-group-item">
									<div className="d-flex w-100 justify-content-between">
										<h5 className="mb-2 mt-0">
											{ thread.status === 'private' && (
												<i className="material-icons">lock</i>
											) }
											<a href={ thread.link }>{ thread.title }</a>
										</h5>
										<small>{ formatDate( thread.date_atom ) }</small>
									</div>
									<p className="mb-1">
										<i className="material-icons">comment</i>
										{ ' ' }
										{ thread.count.approved }
										{ ' ' }
										{ thread.count.approved > 1
											? _x( 'comments', 'thread-comment-count', 'hamethread' )
											: _x( 'comment', 'thread-comment-count', 'hamethread' ) }
										{ thread.resolved && (
											<span className="ms-2 text-success">
												<i className="material-icons text-success">check_circle</i>
												{ ' ' }
												{ __( 'Resolved', 'hamethread' ) }
											</span>
										) }
									</p>
									<small className="text-muted">
										<i className="material-icons">access_time</i>
										{ ' ' }
										{ __( 'Last Updated: ', 'hamethread' ) }
										{ formatDate( thread.latest, 'long' ) }
									</small>
								</li>
							) ) }
						</ul>
						{ total > 1 && (
							<div className="mt-4">
								<Pagination
									total={ total }
									current={ current }
									onPageChanged={ handlePageChange }
								/>
							</div>
						) }
					</>
				) : (
					! loading && (
						<div className="alert alert-secondary">
							{ __( 'No thread is found for your criteria.', 'hamethread' ) }
						</div>
					)
				) }
				<LoadingIndicator loading={ loading } />
			</div>
		</div>
	);
};

// Mount the component
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'hamethread-list' );
	if ( container ) {
		const root = createRoot( container );
		root.render( <ThreadList /> );
	}
} );
