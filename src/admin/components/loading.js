import { Spinner } from '@wordpress/components';

export default function Loading() {
	return (
		<div className="openbb-loading">
			<Spinner />
		</div>
	);
}
