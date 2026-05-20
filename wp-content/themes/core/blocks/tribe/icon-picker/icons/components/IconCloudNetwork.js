import * as React from 'react';
const SvgIconCloudNetwork = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M7 62a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM32 62a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM57 62a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 52V38M53.466 53.466 48 48V38M10.536 53.464 16 48V38"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M50 14c-.346 0-.688.024-1.028.052a17.976 17.976 0 0 0-33.944 0C14.688 14.024 14.346 14 14 14a12 12 0 0 0 0 24h36a12 12 0 0 0 0-24Z"
		/>
	</svg>
);
export default SvgIconCloudNetwork;
